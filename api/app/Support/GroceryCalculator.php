<?php

namespace App\Support;

use App\Models\GroceryItem;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use Illuminate\Support\Collection;

/**
 * Planned meals -> aggregated requirements -> minus usable pantry and items already on the list
 * -> what to buy. Quantities are aggregated in each dimension's base unit and never subtracted
 * across incompatible units.
 */
class GroceryCalculator
{
    /**
     * Sum non-optional ingredient needs of the plans, keyed "ingredientId|dimension".
     *
     * @param  Collection<int, MealPlan>  $plans  with recipe.ingredients.ingredient loaded
     * @return array<string, array{ingredient: Ingredient, unit: Unit, quantity: float}> quantity in base unit
     */
    public static function requirements(Collection $plans): array
    {
        $needs = [];
        foreach ($plans as $plan) {
            foreach ($plan->recipe->ingredients as $ri) {
                if ($ri->optional) {
                    continue;
                }
                $base = $ri->unit->baseUnit();
                $key = $ri->ingredient_id.'|'.$base->dimension();
                $scaled = Quantities::scale($ri->quantity, $plan->recipe->servings, $plan->servings);

                $needs[$key] ??= ['ingredient' => $ri->ingredient, 'unit' => $base, 'quantity' => 0.0];
                $needs[$key]['quantity'] += Unit::convert($scaled, $ri->unit, $base);
            }
        }

        return $needs;
    }

    /**
     * @param  array<string, array{ingredient: Ingredient, unit: Unit, quantity: float}>  $requirements
     * @param  Collection<int, PantryItem>  $pantry  keyed by ingredient_id
     * @param  Collection<int, GroceryItem>  $onList  items already covering needs (not yet in pantry)
     * @return list<array{ingredient: Ingredient, quantity: float, unit: Unit}> in the ingredient's default unit
     */
    public static function shortfall(array $requirements, Collection $pantry, Collection $onList): array
    {
        $toBuy = [];
        foreach ($requirements as $need) {
            $ingredient = $need['ingredient'];
            $base = $need['unit'];

            $have = RecipeMatcher::usableQuantity($pantry->get($ingredient->id), $base);
            $have += $onList->where('ingredient_id', $ingredient->id)
                ->sum(fn (GroceryItem $g) => $g->unit && $g->boughtQuantity() ? (Unit::convert($g->boughtQuantity(), $g->unit, $base) ?? 0) : 0);

            $remaining = $need['quantity'] - $have;
            if ($remaining <= 0.0005) {
                continue;
            }

            $unit = $ingredient->default_unit->isCompatibleWith($base) ? $ingredient->default_unit : $base;
            $quantity = Unit::convert($remaining, $base, $unit);
            // You can't buy half an egg or half a packet.
            if (in_array($unit, [Unit::PIECE, Unit::PACKET], true)) {
                $quantity = ceil($quantity - 0.0005);
            }

            $toBuy[] = ['ingredient' => $ingredient, 'quantity' => round($quantity, 3), 'unit' => $unit];
        }

        return $toBuy;
    }
}
