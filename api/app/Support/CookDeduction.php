<?php

namespace App\Support;

use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What cooking a recipe takes out of the pantry. Deducts min(need, usable stock) so stock never
 * goes negative; expired stock is not used; anything not covered is reported as short.
 */
class CookDeduction
{
    /**
     * @param  Collection<int, PantryItem>  $pantry  keyed by ingredient_id
     */
    public static function preview(Recipe $recipe, Collection $pantry, int $servings): array
    {
        $rows = [];
        foreach ($recipe->ingredients as $ri) {
            $need = Quantities::scale($ri->quantity, $recipe->servings, $servings);
            $item = $pantry->get($ri->ingredient_id);
            $haveInRecipeUnit = RecipeMatcher::usableQuantity($item, $ri->unit);
            $take = min($need, $haveInRecipeUnit);

            $rows[] = [
                'ingredient_id' => $ri->ingredient_id,
                'name' => $ri->ingredient->name,
                'optional' => $ri->optional,
                'need' => $need,
                'unit' => $ri->unit->value,
                'short' => round($need - $take, 3),
                'pantry_item_id' => $take > 0 ? $item->id : null,
                // Deduction expressed in the pantry item's own unit.
                'deduct' => $take > 0 ? Unit::convert($take, $ri->unit, $item->unit) : 0.0,
                'pantry_unit' => $item?->unit->value,
                'pantry_after' => $take > 0 ? round($item->quantity - Unit::convert($take, $ri->unit, $item->unit), 3) : $item?->quantity,
            ];
        }

        return $rows;
    }

    /** Recalculates against locked pantry rows and deducts everything in one transaction. */
    public static function apply(Recipe $recipe, int $householdId, int $servings, ?int $userId = null, ?MealPlan $mealPlan = null): array
    {
        return DB::transaction(function () use ($recipe, $householdId, $servings, $userId, $mealPlan) {
            $pantry = PantryItem::where('household_id', $householdId)
                ->whereIn('ingredient_id', $recipe->ingredients->pluck('ingredient_id'))
                ->lockForUpdate()
                ->with('ingredient')
                ->get()
                ->keyBy('ingredient_id');

            $rows = self::preview($recipe, $pantry, $servings);
            $note = "Cooked {$recipe->name} ×{$servings}";

            foreach ($rows as $row) {
                if ($row['deduct'] > 0) {
                    $item = $pantry->get($row['ingredient_id']);
                    PantryLedger::change($item, -$row['deduct'], $item->unit, TransactionType::COOKED, $userId, $note);
                }
            }

            $mealPlan?->update(['cooked_at' => now()]);

            return $rows;
        });
    }
}
