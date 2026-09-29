<?php

namespace App\Support;

use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Per-serving nutrition: AI estimates it once, code derives tags and totals from the numbers. */
class Nutrition
{
    public const FIELDS = ['calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g'];

    /** Sanity limits per serving; AI answers outside these are rejected. */
    private const MAX = ['calories' => 3000, 'protein_g' => 200, 'carbs_g' => 400, 'fat_g' => 250, 'fiber_g' => 100];

    public const HIGH_PROTEIN_G = 15;

    public const LOW_CALORIE = 350;

    public const HIGH_FIBER_G = 6;

    public const TAGS = ['high_protein', 'low_calorie', 'high_fiber'];

    public static function tags(Recipe $recipe): array
    {
        if ($recipe->calories === null) {
            return [];
        }

        return array_values(array_filter([
            $recipe->protein_g >= self::HIGH_PROTEIN_G ? 'high_protein' : null,
            $recipe->calories <= self::LOW_CALORIE ? 'low_calorie' : null,
            $recipe->fiber_g >= self::HIGH_FIBER_G ? 'high_fiber' : null,
        ]));
    }

    /** SQL version of tags() for filtering. */
    public static function whereTag(Builder $query, string $tag): Builder
    {
        return match ($tag) {
            'high_protein' => $query->where('protein_g', '>=', self::HIGH_PROTEIN_G),
            'low_calorie' => $query->where('calories', '<=', self::LOW_CALORIE),
            'high_fiber' => $query->where('fiber_g', '>=', self::HIGH_FIBER_G),
        };
    }

    /** Ask the AI for per-serving values and validate them. */
    public static function estimate(Recipe $recipe): array
    {
        $recipe->loadMissing('ingredients.ingredient');
        $lines = $recipe->ingredients
            ->map(fn ($ri) => "- {$ri->quantity} {$ri->unit->value} {$ri->ingredient->name}".($ri->optional ? ' (optional)' : ''))
            ->implode("\n");

        $answer = Ai::json(
            'You are a nutritionist. Estimate nutrition using standard food composition values (e.g. USDA / Indian Food Composition Tables).',
            "Recipe: {$recipe->name} (makes {$recipe->servings} servings)\nIngredients for the whole recipe:\n{$lines}\n\n"
            .'Return the estimated nutrition PER SERVING as JSON with numeric keys: '
            .'{"calories": kcal, "protein_g": g, "carbs_g": g, "fat_g": g, "fiber_g": g}',
            300,
        );

        return self::validate($answer);
    }

    public static function validate(array $values): array
    {
        $clean = [];
        foreach (self::FIELDS as $field) {
            $v = $values[$field] ?? null;
            if (! is_numeric($v) || $v < 0 || $v > self::MAX[$field]) {
                throw new AiUnavailable(__('The AI gave an unrealistic nutrition estimate. Please try again.'));
            }
            $clean[$field] = round((float) $v, 1);
        }

        return $clean;
    }

    /**
     * Per-person totals per day, assuming one serving of each planned dish per person.
     *
     * @param  Collection<int, MealPlan>  $plans  with recipe loaded
     * @return array<string, array{calories: float, protein_g: float, carbs_g: float, fat_g: float, fiber_g: float, missing: int}>
     */
    public static function dailyTotals(Collection $plans): array
    {
        $days = [];
        foreach ($plans as $plan) {
            $date = $plan->date->toDateString();
            $days[$date] ??= array_fill_keys(self::FIELDS, 0.0) + ['missing' => 0];

            if ($plan->recipe->calories === null) {
                $days[$date]['missing']++;

                continue;
            }
            foreach (self::FIELDS as $field) {
                $days[$date][$field] = round($days[$date][$field] + (float) $plan->recipe->{$field}, 1);
            }
        }

        return $days;
    }
}
