<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Support\Nutrition;
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
        $start = $request->start ? Carbon::parse($request->start) : Carbon::today()->startOfWeek();
        $end = $start->copy()->addDays(6);

        $plans = MealPlan::where('household_id', $request->user()->household_id)
            ->whereBetween('date', [$start, $end])
            ->with('recipe:id,name,meal_type,servings,prep_time,cook_time,is_veg,calories,protein_g,carbs_g,fat_g,fiber_g,image_path')
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

        return response()->json(['message' => count($data['entries']).' meal(s) added to your plan.'], 201);
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

        return response()->json(['message' => 'Removed from plan.']);
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
