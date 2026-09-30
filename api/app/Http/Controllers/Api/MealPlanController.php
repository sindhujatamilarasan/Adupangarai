<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Support\MealPlanner;
use App\Support\Nutrition;
use App\Support\RecipeMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MealPlanController extends Controller
{
    /** One week starting at `start` (default: this week's Monday). */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['start' => ['nullable', 'date_format:Y-m-d']]);
        $start = $request->start ? Carbon::parse($request->start) : Carbon::today()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

        $plans = MealPlan::where('household_id', $request->user()->household_id)
            ->whereBetween('date', [$start, $end])
            ->with('recipe:id,name,name_ta,meal_type,servings,prep_time,cook_time,is_veg,calories,protein_g,carbs_g,fat_g,fiber_g,image_path')
            ->orderBy('date')->orderBy('id')
            ->get();

        return response()->json([
            'data' => $plans,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'nutrition' => Nutrition::dailyTotals($plans),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $plan = MealPlan::create([...$data, 'household_id' => $request->user()->household_id]);

        return response()->json(['data' => $plan->load('recipe')], 201);
    }

    /** Suggested plan (not saved): rules-based, see MealPlanner. */
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'days' => ['required', 'integer', 'min:1', 'max:7'],
            'meals' => ['required', 'array', 'min:1'],
            'meals.*' => ['distinct', Rule::in(Recipe::MEAL_TYPES)],
            'goal' => ['required', Rule::in(MealPlanner::GOALS)],
            'servings' => ['required', 'integer', 'min:1', 'max:20'],
            'seed' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'diet' => ['nullable', Rule::in(MealPlanner::DIETS)],
        ]);
        $household = $request->user()->household_id;
        $recipes = Recipe::visibleTo($household)->with('ingredients.ingredient.category')->get();
        $matches = RecipeMatcher::matchAll($recipes, RecipeMatcher::pantryFor($household))->keyBy('recipe.id');
        $meals = array_values(array_intersect(Recipe::MEAL_TYPES, $data['meals']));

        $plan = MealPlanner::suggest($recipes, $matches, Carbon::parse($data['start']), $data['days'], $meals, $data['goal'], $data['servings'], $data['seed'] ?? 0, $data['diet'] ?? 'any');
        $labels = $recipes->pluck('label', 'id');

        return response()->json([
            'data' => array_map(fn ($p) => [...$p, 'recipe_name' => $labels[$p['recipe_id']]], $plan),
            'summary' => MealPlanner::summary($plan, $recipes),
        ]);
    }

    /** Start fresh: remove every not-yet-cooked meal between start and end (cooked meals stay as history). */
    public function clear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        $count = MealPlan::where('household_id', $request->user()->household_id)
            ->whereBetween('date', [$data['start'], $data['end']])
            ->whereNull('cooked_at')
            ->delete();

        return response()->json(['message' => __(':count planned meal(s) removed.', ['count' => $count]), 'count' => $count]);
    }

    /** Add several planned meals at once (e.g. an accepted AI plan). All or nothing. */
    public function bulk(Request $request): JsonResponse
    {
        $household = $request->user()->household_id;
        $data = $request->validate([
            'entries' => ['required', 'array', 'min:1', 'max:60'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.meal_type' => ['required', Rule::in(Recipe::MEAL_TYPES)],
            'entries.*.recipe_id' => ['required', Rule::exists('recipes', 'id')->where(
                fn ($q) => $q->whereNull('household_id')->orWhere('household_id', $household)
            )],
            'entries.*.servings' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        DB::transaction(fn () => collect($data['entries'])->each(
            fn ($e) => MealPlan::create([...$e, 'household_id' => $household])
        ));

        return response()->json(['message' => __(':count meal(s) added to your plan.', ['count' => count($data['entries'])])], 201);
    }

    public function update(Request $request, MealPlan $mealPlan): JsonResponse
    {
        $this->ensureOwned($mealPlan);
        $mealPlan->update($this->validated($request, partial: true));

        return response()->json(['data' => $mealPlan->fresh('recipe')]);
    }

    public function destroy(MealPlan $mealPlan): JsonResponse
    {
        $this->ensureOwned($mealPlan);
        $mealPlan->delete();

        return response()->json(['message' => __('Removed from plan.')]);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $household = $request->user()->household_id;

        return $request->validate([
            'date' => [$required, 'date_format:Y-m-d'],
            'meal_type' => [$required, Rule::in(Recipe::MEAL_TYPES)],
            'recipe_id' => [$required, Rule::exists('recipes', 'id')->where(
                fn ($q) => $q->whereNull('household_id')->orWhere('household_id', $household)
            )],
            'servings' => [$required, 'integer', 'min:1', 'max:100'],
        ]);
    }
}
