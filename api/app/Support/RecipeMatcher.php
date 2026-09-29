<?php

namespace App\Support;

use App\Models\PantryItem;
use App\Models\Recipe;
use Illuminate\Support\Collection;

/**
 * Compares a recipe (scaled to servings) with pantry stock.
 * Expired stock is not usable. Optional ingredients never lower the match %.
 */
class RecipeMatcher
{
    public const AVAILABLE = 'available';

    public const ALMOST = 'almost';

    public const UNAVAILABLE = 'unavailable';

    /** "Almost available": at most this many required ingredients short... */
    public const ALMOST_MAX_SHORT = 2;

    /** ...and at least this match %. */
    public const ALMOST_MIN_PERCENT = 50;

    /**
     * @param  Collection<int, PantryItem>  $pantry  keyed by ingredient_id
     */
    public static function match(Recipe $recipe, Collection $pantry, ?int $servings = null): array
    {
        $servings ??= $recipe->servings;
        $result = ['available' => [], 'missing' => [], 'insufficient' => [], 'optional_missing' => [], 'uses_expiring' => []];
        $score = 0.0;
        $required = 0;

        foreach ($recipe->ingredients as $ri) {
            $need = Quantities::scale($ri->quantity, $recipe->servings, $servings);
            $item = $pantry->get($ri->ingredient_id);
            $have = self::usableQuantity($item, $ri->unit);
            $row = [
                'ingredient_id' => $ri->ingredient_id,
                'name' => $ri->ingredient->name,
                'unit' => $ri->unit->value,
                'need' => $need,
                'have' => $have,
                'optional' => $ri->optional,
            ];

            if ($have > 0 && $item->expiry_status === ExpiryStatus::EXPIRING_SOON) {
                $result['uses_expiring'][] = ['ingredient_id' => $ri->ingredient_id, 'name' => $ri->ingredient->name, 'days_to_expiry' => $item->days_to_expiry];
            }

            if (! $ri->optional) {
                $required++;
                $score += min($have / $need, 1);
            }

            if ($have >= $need) {
                $result['available'][] = $row;
            } elseif ($ri->optional) {
                $result['optional_missing'][] = [...$row, 'short' => round($need - $have, 3)];
            } elseif ($have > 0) {
                $result['insufficient'][] = [...$row, 'short' => round($need - $have, 3)];
            } else {
                $result['missing'][] = [...$row, 'short' => $need];
            }
        }

        $percent = $required ? (int) floor($score / $required * 100) : 100;
        $short = count($result['missing']) + count($result['insufficient']);

        return [
            'servings' => $servings,
            'match_percent' => $percent,
            'status' => match (true) {
                $short === 0 => self::AVAILABLE,
                $short <= self::ALMOST_MAX_SHORT && $percent >= self::ALMOST_MIN_PERCENT => self::ALMOST,
                default => self::UNAVAILABLE,
            },
            ...$result,
        ];
    }

    /** Pantry stock in the recipe's unit; 0 if absent, expired or not convertible. */
    public static function usableQuantity(?PantryItem $item, Unit $unit): float
    {
        if (! $item || $item->expiry_status === ExpiryStatus::EXPIRED) {
            return 0.0;
        }

        return Unit::convert($item->quantity, $item->unit, $unit) ?? 0.0;
    }

    /** Pantry items of a household keyed by ingredient_id, ready for match(). */
    public static function pantryFor(int $householdId): Collection
    {
        return PantryItem::where('household_id', $householdId)->get()->keyBy('ingredient_id');
    }

    /**
     * @param  Collection<int, Recipe>  $recipes  with ingredients.ingredient loaded
     * @return Collection<int, array{recipe: array, match: array}>
     */
    public static function matchAll(Collection $recipes, Collection $pantry): Collection
    {
        return $recipes->map(fn (Recipe $r) => [
            'recipe' => $r->only(['id', 'name', 'description', 'meal_type', 'cuisine', 'servings', 'total_time', 'is_veg', 'calories', 'protein_g', 'health_tags', 'image_url']),
            'match' => self::match($r, $pantry),
        ]);
    }

    /** Best match first, then quickest, then name. */
    public static function rank(Collection $results): Collection
    {
        return $results->sortBy([
            fn ($a, $b) => $b['match']['match_percent'] <=> $a['match']['match_percent'],
            fn ($a, $b) => $a['recipe']['total_time'] <=> $b['recipe']['total_time'],
            fn ($a, $b) => $a['recipe']['name'] <=> $b['recipe']['name'],
        ])->values();
    }

    /**
     * Recipes that use stock expiring soon: nearest expiry first, then those using more
     * expiring items, then the ones you can most nearly cook.
     */
    public static function useSoon(Collection $results): Collection
    {
        $soonest = fn ($r) => min(array_column($r['match']['uses_expiring'], 'days_to_expiry'));

        return $results->filter(fn ($r) => $r['match']['uses_expiring'])->sortBy([
            fn ($a, $b) => $soonest($a) <=> $soonest($b),
            fn ($a, $b) => count($b['match']['uses_expiring']) <=> count($a['match']['uses_expiring']),
            fn ($a, $b) => $b['match']['match_percent'] <=> $a['match']['match_percent'],
            fn ($a, $b) => $a['recipe']['name'] <=> $b['recipe']['name'],
        ])->values();
    }
}
