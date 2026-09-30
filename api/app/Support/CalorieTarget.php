<?php

namespace App\Support;

use App\Models\HealthProfile;

/**
 * Daily calorie and protein targets from standard formulas (no AI):
 * Mifflin-St Jeor BMR x activity factor, then a deficit/surplus for the chosen pace
 * (1 kg of body weight ~ 7700 kcal). Weight loss never goes below a safe floor.
 */
class CalorieTarget
{
    public const GOALS = ['lose', 'maintain', 'gain'];

    public const ACTIVITY = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725];

    public const PACES = ['lose' => [0.25, 0.5, 0.75], 'gain' => [0.25, 0.5]];

    public const KCAL_PER_KG = 7700;

    /** Common minimum daily intake without medical supervision. */
    public const FLOOR = ['female' => 1200, 'male' => 1500];

    /** Adult BMI range used to keep a target weight sensible. */
    public const HEALTHY_BMI = [18.5, 24.9];

    /** Rough energy of one step at a normal walk: 0.0005 kcal per kg of body weight. */
    public const KCAL_PER_STEP_PER_KG = 0.0005;

    public static function bmr(HealthProfile $p, float $weight, int $year): float
    {
        $age = $year - $p->birth_year;

        return 10 * $weight + 6.25 * $p->height_cm - 5 * $age + ($p->sex === 'male' ? 5 : -161);
    }

    /**
     * @return array{calories: int, protein_g: int, bmr: int, maintenance: int, floored: bool}
     */
    public static function for(HealthProfile $p, float $weight, int $year): array
    {
        $bmr = self::bmr($p, $weight, $year);
        $maintenance = $bmr * self::ACTIVITY[$p->activity];
        $daily = $p->pace_kg * self::KCAL_PER_KG / 7;

        $calories = match ($p->goal) {
            'lose' => $maintenance - $daily,
            'gain' => $maintenance + $daily,
            default => $maintenance,
        };
        $floor = self::FLOOR[$p->sex];
        $floored = $p->goal === 'lose' && $calories < $floor;

        return [
            'calories' => (int) (round(max($calories, $p->goal === 'lose' ? $floor : 0) / 10) * 10),
            // 1.6 g/kg while changing weight (keeps muscle), 1.2 g/kg otherwise; per kg of a healthy weight for big bodies.
            'protein_g' => (int) round(($p->goal === 'maintain' ? 1.2 : 1.6) * min($weight, self::weightAtBmi($p->height_cm, 25))),
            'bmr' => (int) round($bmr),
            'maintenance' => (int) round($maintenance),
            'floored' => $floored,
        ];
    }

    public static function bmi(float $weight, float $heightCm): float
    {
        return round($weight / ($heightCm / 100) ** 2, 1);
    }

    public static function weightAtBmi(float $heightCm, float $bmi): float
    {
        return round($bmi * ($heightCm / 100) ** 2, 1);
    }

    /** Whether a day's intake counts as "on target" for the goal (small range, not an exact number). */
    public static function onTarget(string $goal, float $intake, int $target): bool
    {
        [$low, $high] = self::range($goal);

        return $intake >= $target * $low && $intake <= $target * $high;
    }

    /** @return array{float, float} the on-target band as shares of the target */
    public static function range(string $goal): array
    {
        return match ($goal) {
            'lose' => [0.8, 1.05],
            'gain' => [0.95, 1.2],
            default => [0.9, 1.1],
        };
    }

    public static function stepsFor(float $kcal, float $weight): int
    {
        return (int) (ceil($kcal / (self::KCAL_PER_STEP_PER_KG * $weight) / 100) * 100);
    }
}
