<?php

namespace App\Support;

use App\Models\GroceryItem;
use App\Models\Ingredient;
use App\Models\Recipe;

/**
 * Estimated money, from each ingredient's usual shop pack price (approximate; for planning, not billing).
 */
class Cost
{
    /** ₹ per one of the ingredient's own unit (per g, per tsp, per piece…), or null if we don't know its price. */
    public static function unitPrice(Ingredient $i): ?float
    {
        return $i->pack_price && $i->pack_quantity ? $i->pack_price / $i->pack_quantity : null;
    }

    /** ₹ for one serving (optional ingredients and unpriced ones like water left out). Needs ingredients.ingredient loaded. */
    public static function perServing(Recipe $r): float
    {
        $total = 0.0;
        foreach ($r->ingredients as $ri) {
            $price = $ri->optional ? null : self::unitPrice($ri->ingredient);
            $qty = $price === null ? null : Unit::convert($ri->quantity, $ri->unit, $ri->ingredient->default_unit);
            $total += $qty === null ? 0 : $qty * $price;
        }

        return round($total / max($r->servings, 1), 1);
    }

    /** ₹ to buy what's left on a grocery list item (whole packs when it has them). */
    public static function forItem(GroceryItem $item): float
    {
        $i = $item->ingredient;
        if (! $i || ! $i->pack_price) {
            return 0.0;
        }
        if ($item->packs) {
            return $item->packs * $i->pack_price;
        }
        $price = self::unitPrice($i);
        $qty = $item->quantity && $item->unit ? Unit::convert($item->quantity, $item->unit, $i->default_unit) : null;

        return $price && $qty ? round($qty * $price, 1) : 0.0;
    }
}
