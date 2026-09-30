<?php

namespace App\Support;

use App\Models\Recipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Suggests a meal plan with plain rules (no AI), so goals and variety are guaranteed:
 * - each slot gets the best-scoring recipe for its meal time and the chosen goal;
 * - a dish comes back at most twice a week and never within 3 days;
 * - two dishes on the same day may not be near-duplicates (e.g. two fried rices);
 * - an expiring ingredient earns its bonus only once, so it can't dominate the plan.
 */
class MealPlanner
{
    public const GOALS = ['balanced', 'high_protein', 'low_calorie', 'my_target'];

    /** Share of the day's calories each meal usually takes (used by the "my target" goal). */
    public const MEAL_SHARE = ['breakfast' => 0.25, 'lunch' => 0.35, 'snack' => 0.1, 'dinner' => 0.3];

    /** any = everything; veg = vegetarian only; nonveg = everything, leaning to meat/fish/egg dishes. */
    public const DIETS = ['any', 'veg', 'nonveg'];

    /** Everyday basics ignored when comparing dishes (almost every recipe uses them). */
    private const STAPLES = [
        'salt', 'cooking oil', 'onion', 'green chilli', 'mustard seed', 'turmeric powder', 'curry leaf', 'ginger',
        'garlic', 'cumin seed', 'hing', 'water', 'coriander leaf', 'red chilli powder', 'ghee', 'sugar',
    ];

    /** Days that must pass before a dish may be served again, and how often it may appear per plan. */
    public const REPEAT_GAP_DAYS = 3;

    public const MAX_USES = 2;

    /** A snack/drink only stands in for a breakfast if it is a real meal. */
    public const MIN_MEAL_KCAL = 180;

    /** Two dishes sharing at least this share of main ingredients count as "the same kind of dish". */
    public const SIMILARITY_LIMIT = 0.5;

    /**
     * @param  Collection<int, Recipe>  $recipes  with ingredients.ingredient loaded
     * @param  Collection<int, array{recipe: array, match: array}>  $matches  keyed by recipe id
     * @return list<array{date: string, meal_type: string, recipe_id: int, servings: int}>
     */
    public static function suggest(Collection $recipes, Collection $matches, Carbon $start, int $days, array $meals, string $goal, int $servings, int $seed = 0, string $diet = 'any', ?int $targetKcal = null): array
    {
        if ($diet === 'veg') {
            $recipes = $recipes->where('is_veg', true)->values();
        }

        mt_srand($seed);
        $jitter = $recipes->mapWithKeys(fn (Recipe $r) => [$r->id => mt_rand(0, 800) / 1000])->all();
        $main = $recipes->mapWithKeys(fn (Recipe $r) => [$r->id => self::mainIngredients($r)])->all();
        $hero = $recipes->mapWithKeys(fn (Recipe $r) => [$r->id => self::heroIngredients($r)])->all();

        $used = [];
        $expiringUsed = [];
        $plan = [];

        for ($d = 0; $d < $days; $d++) {
            $today = [];
            $eaten = 0.0;
            foreach ($meals as $k => $meal) {
                // "My target": each meal gets its share of whatever is left of the day's calories.
                $shareLeft = array_sum(array_map(fn ($m) => self::MEAL_SHARE[$m], array_slice($meals, $k)));
                $budget = $targetKcal ? ($targetKcal - $eaten) * self::MEAL_SHARE[$meal] / $shareLeft : null;
                $best = null;
                $bestScore = -INF;
                foreach ($recipes as $r) {
                    $uses = $used[$r->id] ?? [];
                    if ($uses && ($d - max($uses) < self::REPEAT_GAP_DAYS || count($uses) >= self::MAX_USES)) {
                        continue;
                    }
                    $fit = self::fit($meal, $r->meal_type);
                    if ($fit !== null && $meal === 'breakfast' && $r->meal_type === 'snack' && $r->calories !== null && $r->calories < self::MIN_MEAL_KCAL) {
                        $fit = null;
                    }
                    if ($fit === null || in_array($r->id, $today, true)) {
                        continue;
                    }
                    foreach ($today as $other) {
                        if (self::similarity($main[$r->id], $main[$other]) >= self::SIMILARITY_LIMIT) {
                            continue 2;
                        }
                    }
                    $match = $matches[$r->id]['match'] ?? ['match_percent' => 0, 'uses_expiring' => []];
                    $freshExpiring = array_diff(array_column($match['uses_expiring'], 'ingredient_id'), $expiringUsed);

                    $sameHero = collect($today)->contains(fn ($other) => array_intersect($hero[$r->id], $hero[$other]));

                    $score = $fit
                        - ($sameHero ? 3 : 0)
                        + ($budget !== null ? self::budgetScore($r, $budget) : self::goalScore($goal, $r))
                        + $match['match_percent'] / 50
                        + ($freshExpiring ? 1.5 : 0)
                        + $jitter[$r->id]
                        - ($uses ? 2 : 0) // prefer something new, but a great fit may return
                        + ($diet === 'nonveg' && ! $r->is_veg ? 2 : 0);

                    if ($score > $bestScore) {
                        [$best, $bestScore] = [$r, $score];
                    }
                }
                if (! $best) {
                    continue;
                }

                $today[] = $best->id;
                $eaten += (float) $best->calories;
                $used[$best->id][] = $d;
                $expiringUsed = array_merge($expiringUsed, array_column($matches[$best->id]['match']['uses_expiring'] ?? [], 'ingredient_id'));
                $plan[] = ['date' => $start->copy()->addDays($d)->toDateString(), 'meal_type' => $meal, 'recipe_id' => $best->id, 'servings' => $servings];
            }
        }

        return $plan;
    }

    /** How well a recipe's usual meal time suits the slot; null = don't use it there. */
    public static function fit(string $slot, string $recipeMeal): ?float
    {
        if ($slot === $recipeMeal) {
            return 3;
        }

        return match ([$slot, $recipeMeal]) {
            ['lunch', 'dinner'], ['dinner', 'lunch'] => 1.5,
            ['breakfast', 'snack'], ['snack', 'breakfast'] => 1,
            default => null,
        };
    }

    public static function goalScore(string $goal, Recipe $r): float
    {
        if ($r->calories === null) {
            return $goal === 'balanced' ? 0 : -1; // unknown nutrition can't prove it meets a goal
        }

        return match ($goal) {
            'high_protein' => $r->protein_g / 5,                                    // 30 g -> +6
            // 200 kcal -> +5; very small portions aren't a real meal, so they don't win on "lighter"
            'low_calorie' => $r->calories < self::MIN_MEAL_KCAL ? -2 : max(-3, min(5, (600 - $r->calories) / 80)),
            default => count($r->health_tags) * 0.8 + ($r->calories >= 250 && $r->calories <= 550 ? 1 : 0),
        };
    }

    /** Closest to the meal's calorie budget wins (one serving per person); protein is a small bonus. */
    public static function budgetScore(Recipe $r, float $budget): float
    {
        if ($r->calories === null) {
            return -3;
        }
        $off = abs($r->calories - $budget) / max($budget, 1);

        return max(-6, 4 - 8 * $off) + $r->protein_g / 15;
    }

    /** @return list<int> non-optional, non-staple ingredient ids */
    public static function mainIngredients(Recipe $r): array
    {
        return $r->ingredients
            ->filter(fn ($ri) => ! $ri->optional && ! in_array($ri->ingredient->normalized_name, self::STAPLES, true))
            ->pluck('ingredient_id')->values()->all();
    }

    /** The "star" of a dish (meat, fish, paneer): best not served twice in one day. */
    public static function heroIngredients(Recipe $r): array
    {
        return $r->ingredients
            ->filter(fn ($ri) => ! $ri->optional && ($ri->ingredient->category?->name === 'Meat & Seafood' || $ri->ingredient->normalized_name === 'paneer'))
            ->pluck('ingredient_id')->values()->all();
    }

    /** Jaccard overlap of two ingredient lists (0 = nothing shared, 1 = identical). */
    public static function similarity(array $a, array $b): float
    {
        $union = count(array_unique([...$a, ...$b]));

        return $union ? count(array_intersect($a, $b)) / $union : 0;
    }

    /** Per-person daily totals and averages for a suggested plan. */
    public static function summary(array $plan, Collection $recipes): array
    {
        $byId = $recipes->keyBy('id');
        $days = [];
        foreach ($plan as $p) {
            $r = $byId[$p['recipe_id']];
            $days[$p['date']] ??= ['calories' => 0.0, 'protein_g' => 0.0];
            $days[$p['date']]['calories'] += (float) $r->calories;
            $days[$p['date']]['protein_g'] += (float) $r->protein_g;
        }
        $n = max(count($days), 1);

        return [
            'per_day' => $days,
            'avg_calories' => round(array_sum(array_column($days, 'calories')) / $n),
            'avg_protein_g' => round(array_sum(array_column($days, 'protein_g')) / $n, 1),
        ];
    }
}
