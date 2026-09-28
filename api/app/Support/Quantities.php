<?php

namespace App\Support;

class Quantities
{
    /** Scale a recipe quantity from the recipe's servings to the wanted servings. */
    public static function scale(float $quantity, int $fromServings, int $toServings): float
    {
        if ($fromServings <= 0 || $toServings <= 0) {
            throw new \InvalidArgumentException('Servings must be positive.');
        }

        return round($quantity * $toServings / $fromServings, 3);
    }
}
