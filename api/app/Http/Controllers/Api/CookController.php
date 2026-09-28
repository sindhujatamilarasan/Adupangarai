<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Support\Nutrition;
use App\Support\RecipeMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CookController extends Controller
{
    /** "What can I cook?" — every visible recipe matched against the pantry, best first. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'available', 'almost', 'use_soon'])],
            'meal_type' => ['nullable', Rule::in(Recipe::MEAL_TYPES)],
            'max_time' => ['nullable', 'integer', 'min:1'],
            'health' => ['nullable', Rule::in(Nutrition::TAGS)],
        ]);
        $household = $request->user()->household_id;
        $pantry = RecipeMatcher::pantryFor($household);

        $recipes = Recipe::visibleTo($household)
            ->with('ingredients.ingredient')
            ->when($filters['meal_type'] ?? null, fn ($q, $t) => $q->where('meal_type', $t))
            ->when($filters['max_time'] ?? null, fn ($q, $t) => $q->whereRaw('prep_time + cook_time <= ?', [$t]))
            ->when($filters['health'] ?? null, fn ($q, $tag) => Nutrition::whereTag($q, $tag))
            ->get();

        $results = RecipeMatcher::matchAll($recipes, $pantry);

        $results = match ($filters['filter'] ?? 'all') {
            'available' => RecipeMatcher::rank($results->where('match.status', RecipeMatcher::AVAILABLE)),
            'almost' => RecipeMatcher::rank($results->where('match.status', RecipeMatcher::ALMOST)),
            'use_soon' => RecipeMatcher::useSoon($results),
            default => RecipeMatcher::rank($results),
        };

        return response()->json(['data' => $results, 'pantry_count' => $pantry->count()]);
    }
}
