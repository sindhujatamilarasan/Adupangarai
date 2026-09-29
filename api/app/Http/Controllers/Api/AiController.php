<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Support\AiDrafts;
use App\Support\RecipeMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** AI suggestions only. Every endpoint returns a draft for the user to review; nothing is saved. */
class AiController extends Controller
{
    public function pantryParse(Request $request): JsonResponse
    {
        $text = $request->validate(['text' => ['required', 'string', 'max:2000']])['text'];

        $result = AiDrafts::pantryItems($text);

        return response()->json(['data' => $result['items'], 'source' => $result['source']]);
    }

    public function recipeParse(Request $request): JsonResponse
    {
        $text = $request->validate(['text' => ['required', 'string', 'max:6000']])['text'];

        return response()->json(['data' => AiDrafts::recipe($text)]);
    }

    public function mealPlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'days' => ['required', 'integer', 'min:1', 'max:7'],
            'meals' => ['required', 'array', 'min:1'],
            'meals.*' => ['distinct', Rule::in(Recipe::MEAL_TYPES)],
            'goal' => ['required', Rule::in(['balanced', 'high_protein', 'low_calorie'])],
            'servings' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $household = $request->user()->household_id;

        $candidates = RecipeMatcher::rank(RecipeMatcher::matchAll(
            Recipe::visibleTo($household)->with('ingredients.ingredient')->get(),
            RecipeMatcher::pantryFor($household),
        ));

        $plan = AiDrafts::mealPlan($candidates, Carbon::parse($data['start']), $data['days'], $data['meals'], $data['goal'], $data['servings']);
        $names = $candidates->pluck('recipe.label', 'recipe.id');

        return response()->json([
            'data' => array_map(fn ($p) => [...$p, 'recipe_name' => $names[$p['recipe_id']]], $plan),
        ]);
    }
}
