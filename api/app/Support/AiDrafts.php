<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\Recipe;

/**
 * Turns free text into *drafts* via the AI, then checks every value in code.
 * Nothing here writes to the database; the user reviews and confirms each draft.
 */
class AiDrafts
{
    private const ITEM_SHAPE = '{"name": "ingredient name in English, singular", "quantity": number, "unit": "g|kg|ml|L|cup|tbsp|tsp|piece|packet|dozen"}';

    /**
     * "I bought 1 kg chicken, 12 eggs and 2 litres milk" -> pantry rows.
     * Simple lists are read instantly in code; the AI is only asked when that fails.
     *
     * @return array{items: list<array>, source: 'rules'|'ai'}
     */
    public static function pantryItems(string $text): array
    {
        $quick = QuickParse::items($text);
        if ($quick !== null) {
            $rows = array_map(fn ($row) => self::ingredientRow($row), $quick);
            if (! array_filter($rows, fn ($r) => $r['problem'] === 'unknown_ingredient')) {
                return ['items' => $rows, 'source' => 'rules'];
            }
        }

        $answer = Ai::json(
            'You extract grocery items and quantities from what a user said (English, Tamil or mixed). Translate ingredient names to English.',
            "Text: \"{$text}\"\n\nReturn {\"items\": [".self::ITEM_SHAPE.', "expiry_days": number or null]} . Use piece for countable things like eggs.',
            600,
        );

        return [
            'items' => array_values(array_map(fn ($row) => self::ingredientRow($row), array_filter((array) ($answer['items'] ?? []), 'is_array'))),
            'source' => 'ai',
        ];
    }

    /** A recipe dictated in plain words -> recipe form draft (with estimated nutrition). */
    public static function recipe(string $text): array
    {
        $a = Ai::json(
            'You turn a spoken recipe (English, Tamil or mixed) into structured data. Write everything in English. Estimate nutrition per serving from standard food tables.',
            "Recipe as spoken: \"{$text}\"\n\nReturn {\"name\": string, \"description\": short string, \"meal_type\": \"breakfast|lunch|snack|dinner\", "
            .'"cuisine": string, "servings": integer, "prep_time": minutes, "cook_time": minutes, "is_veg": boolean, '
            .'"ingredients": ['.substr(self::ITEM_SHAPE, 0, -1).', "optional": boolean}], "steps": [string], '
            .'"nutrition": {"calories": kcal, "protein_g": g, "carbs_g": g, "fat_g": g, "fiber_g": g}}',
            1500,
        );

        $int = fn ($v, $min, $max, $default) => is_numeric($v) ? max($min, min($max, (int) round($v))) : $default;

        try {
            $nutrition = Nutrition::validate((array) ($a['nutrition'] ?? []));
        } catch (AiUnavailable) {
            $nutrition = array_fill_keys(Nutrition::FIELDS, null); // recipe is still useful without it
        }

        return [
            'name' => mb_substr(trim((string) ($a['name'] ?? '')), 0, 120) ?: 'New recipe',
            'description' => mb_substr(trim((string) ($a['description'] ?? '')), 0, 500) ?: null,
            'meal_type' => in_array($a['meal_type'] ?? null, Recipe::MEAL_TYPES, true) ? $a['meal_type'] : 'lunch',
            'cuisine' => mb_substr(trim((string) ($a['cuisine'] ?? '')), 0, 50) ?: null,
            'servings' => $int($a['servings'] ?? null, 1, 100, 2),
            'prep_time' => $int($a['prep_time'] ?? null, 0, 1440, 10),
            'cook_time' => $int($a['cook_time'] ?? null, 0, 1440, 20),
            'is_veg' => (bool) ($a['is_veg'] ?? true),
            'ingredients' => array_values(array_map(
                fn ($row) => [...self::ingredientRow($row), 'optional' => (bool) ($row['optional'] ?? false)],
                array_filter((array) ($a['ingredients'] ?? []), 'is_array'),
            )),
            'steps' => array_values(array_filter(array_map(fn ($s) => mb_substr(trim((string) $s), 0, 1000), (array) ($a['steps'] ?? [])))),
            ...$nutrition,
        ];
    }

    /** Map an AI item to a known ingredient and a compatible unit; problems and guesses are flagged for the user. */
    public static function ingredientRow(array $row): array
    {
        $name = trim((string) ($row['name'] ?? ''));

        // Small models sometimes put the amount in the name: "2 tablespoons oil".
        $split = $name !== '' ? QuickParse::items($name) : null;
        if ($split && count($split) === 1) {
            $name = $split[0]['name'];
            $row['quantity'] ??= $split[0]['quantity'];
            $row['unit'] ??= $split[0]['unit'];
        }

        [$ingredient, $guessed] = $name === '' ? [null, false] : self::findIngredient($name);
        $loose = Unit::fromLoose($row['unit'] ?? null);
        $multiplier = $loose[1] ?? 1;
        // Models sometimes say "12 dozen" for twelve eggs; 12+ with "dozen" is taken as pieces already.
        if ($multiplier === 12 && is_numeric($row['quantity'] ?? null) && $row['quantity'] >= 12) {
            $multiplier = 1;
        }
        $quantity = is_numeric($row['quantity'] ?? null) ? (float) $row['quantity'] * $multiplier : null;
        $unit = $loose[0] ?? null;

        $problem = match (true) {
            ! $ingredient => 'unknown_ingredient',
            ! $quantity || $quantity <= 0 => 'no_quantity',
            ! $unit || ! $ingredient->acceptsUnit($unit) => 'unit_mismatch',
            $guessed => 'guessed',
            default => null,
        };

        return [
            'heard' => $name,
            'ingredient_id' => $ingredient?->id,
            'name' => $ingredient?->label ?? $name,
            'quantity' => $quantity ? round($quantity, 3) : null,
            // Fall back to the ingredient's own unit so the user only has to fix the number.
            'unit' => ($unit && $ingredient?->acceptsUnit($unit)) ? $unit->value : $ingredient?->default_unit->value,
            'expiry_days' => is_numeric($row['expiry_days'] ?? null) ? max(0, min(3650, (int) $row['expiry_days'])) : null,
            'problem' => $problem,
        ];
    }

    /**
     * Exact normalised match first; otherwise the most specific known name inside what was heard
     * ("fresh coriander leaves" -> Coriander Leaves), else the shortest known name containing it
     * ("oil" -> Cooking Oil). Returns [ingredient|null, wasGuessed].
     */
    public static function findIngredient(string $name): array
    {
        $key = Ingredient::normalize($name);
        if ($exact = Ingredient::where('normalized_name', $key)->first()) {
            return [$exact, false];
        }
        if (mb_strlen($key) < 3) {
            return [null, false];
        }

        $all = Ingredient::all();
        $inside = $all->filter(fn ($i) => preg_match('/\\b'.preg_quote($i->normalized_name, '/').'\\b/u', $key))
            ->sortByDesc(fn ($i) => mb_strlen($i->normalized_name))->first();
        $containing = $all->filter(fn ($i) => preg_match('/\\b'.preg_quote($key, '/').'\\b/u', $i->normalized_name))
            ->sortBy(fn ($i) => mb_strlen($i->normalized_name))->first();

        $guess = $inside ?? $containing;

        return [$guess, (bool) $guess];
    }
}
