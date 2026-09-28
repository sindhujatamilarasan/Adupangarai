<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
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
        ]);
        $household = $request->user()->household_id;
        $pantry = RecipeMatcher::pantryFor($household);

        $recipes = Recipe::visibleTo($household)
            ->with('ingredients.ingredient')
            ->when($filters['meal_type'] ?? null, fn ($q, $t) => $q->where('meal_type', $t))
            ->when($filters['max_time'] ?? null, fn ($q, $t) => $q->whereRaw('prep_time + cook_time <= ?', [$t]))
            ->get();

        $results = $recipes->map(fn (Recipe $r) => [
            'recipe' => $r->only(['id', 'name', 'description', 'meal_type', 'cuisine', 'servings', 'total_time', 'is_veg']),
            'match' => RecipeMatcher::match($r, $pantry),
        ]);

        $results = match ($filters['filter'] ?? 'all') {
            'available' => $results->where('match.status', RecipeMatcher::AVAILABLE),
            'almost' => $results->where('match.status', RecipeMatcher::ALMOST),
            'use_soon' => $results->filter(fn ($r) => $r['match']['uses_expiring'])
                ->sortBy([
                    fn ($a, $b) => self::soonest($a) <=> self::soonest($b),
                    fn ($a, $b) => $b['match']['match_percent'] <=> $a['match']['match_percent'],
                ]),
            default => $results,
        };

        if (($filters['filter'] ?? 'all') !== 'use_soon') {
            $results = $results->sortBy([
                fn ($a, $b) => $b['match']['match_percent'] <=> $a['match']['match_percent'],
                fn ($a, $b) => $a['recipe']['total_time'] <=> $b['recipe']['total_time'],
                fn ($a, $b) => $a['recipe']['name'] <=> $b['recipe']['name'],
            ]);
        }

        return response()->json(['data' => $results->values(), 'pantry_count' => $pantry->count()]);
    }

    private static function soonest(array $result): int
    {
        return min(array_column($result['match']['uses_expiring'], 'days_to_expiry'));
    }
}
