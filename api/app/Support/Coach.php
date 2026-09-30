<?php

namespace App\Support;

use App\Models\FoodLog;
use App\Models\HealthProfile;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\StepLog;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The daily coach, all plain rules: what you ate vs your target, streaks, badges and
 * friendly tips. Badges are worked out from the logs every time, so they can never drift.
 */
class Coach
{
    /** Common extras, for one-tap logging (per piece / glass / serving; typical Indian home values). */
    public const QUICK_FOODS = [
        ['name' => 'Tea with milk and sugar', 'calories' => 60, 'protein_g' => 2],
        ['name' => 'Filter coffee', 'calories' => 80, 'protein_g' => 2],
        ['name' => 'Biscuits (2)', 'calories' => 90, 'protein_g' => 1],
        ['name' => 'Vada (1)', 'calories' => 140, 'protein_g' => 4],
        ['name' => 'Bajji (2)', 'calories' => 180, 'protein_g' => 3],
        ['name' => 'Samosa (1)', 'calories' => 250, 'protein_g' => 4],
        ['name' => 'Murukku (handful)', 'calories' => 150, 'protein_g' => 2],
        ['name' => 'Sweet (1 piece)', 'calories' => 150, 'protein_g' => 2],
        ['name' => 'Banana (1)', 'calories' => 105, 'protein_g' => 1],
        ['name' => 'Boiled egg (1)', 'calories' => 78, 'protein_g' => 6],
        ['name' => 'Extra rice (1 cup)', 'calories' => 200, 'protein_g' => 4],
        ['name' => 'Chapati (1)', 'calories' => 100, 'protein_g' => 3],
        ['name' => 'Fruit juice (1 glass)', 'calories' => 120, 'protein_g' => 1],
        ['name' => 'Soft drink (1 glass)', 'calories' => 100, 'protein_g' => 0],
    ];

    /** Badge => what earns it. Order is the display order. */
    public const BADGES = [
        'started' => 'Set your goal',
        'first_log' => 'Logged your first meal',
        'plan_follower' => 'Ate every planned meal in a day, on target',
        'plan_streak_3' => 'Followed the plan 3 days in a row',
        'on_target_3' => '3 days in a row on target',
        'on_target_7' => '7 days in a row on target',
        'on_target_21' => '21 days in a row on target',
        'step_goal' => 'Reached your step goal',
        'step_streak_7' => 'Step goal 7 days in a row',
        'steps_100k' => '100,000 steps in total',
        'protein_5' => 'Hit your protein target on 5 days',
        'honest_5' => 'Logged extra food 5 times — honesty is progress',
        'first_kg' => 'First kg towards your goal',
        'halfway' => 'Halfway to your target weight',
        'goal_reached' => 'Reached your target weight',
    ];

    /** How far back streaks and badges look. */
    public const HISTORY_DAYS = 120;

    public static function currentWeight(User $user, HealthProfile $p): float
    {
        return (float) (WeightLog::where('user_id', $user->id)->orderByDesc('date')->value('weight_kg') ?? $p->start_weight_kg);
    }

    public static function build(User $user, HealthProfile $p, Carbon $date): array
    {
        $weight = self::currentWeight($user, $p);
        $target = CalorieTarget::for($p, $weight, $date->year);
        $from = Carbon::parse($p->started_on)->max($date->copy()->subDays(self::HISTORY_DAYS));
        $days = self::days($user, $p, $target, $from, $date);
        $weights = WeightLog::where('user_id', $user->id)->where('date', '>=', $date->copy()->subDays(90))->orderBy('date')->get();

        $today = self::today($user, $date);
        $todayRow = $days[$date->toDateString()] ?? null;
        // Today still counts as "in progress" unless it has clearly gone over.
        $inProgress = $date->isToday() && ($todayRow['calories'] ?? 0) <= $target['calories'] * CalorieTarget::range($p->goal)[1];
        $streaks = [
            'on_target' => self::streak($days, 'on_target', $inProgress),
            'steps' => self::streak($days, 'step_goal', $date->isToday()),
            'plan' => self::streak($days, 'plan_followed', $inProgress),
        ];
        $progress = self::progress($p, $weight);

        return [
            'profile' => $p,
            'current_weight' => $weight,
            'bmi' => CalorieTarget::bmi($weight, $p->height_cm),
            'healthy_weight' => [CalorieTarget::weightAtBmi($p->height_cm, CalorieTarget::HEALTHY_BMI[0]), CalorieTarget::weightAtBmi($p->height_cm, CalorieTarget::HEALTHY_BMI[1])],
            'target' => $target,
            'today' => [...$today, 'steps' => $todayRow['steps'] ?? 0],
            'week' => array_values(array_map(
                fn ($d) => array_intersect_key($d, array_flip(['date', 'calories', 'steps', 'on_target', 'logged'])),
                array_slice($days, -7),
            )),
            'streaks' => $streaks,
            'badges' => self::badges($user, $p, $days, $weights),
            'tips' => self::tips($p, $target, $today, $todayRow['steps'] ?? 0, $streaks, $progress, $weight, $date),
            'weights' => $weights,
            'progress' => $progress,
            'quick_foods' => array_map(fn ($f) => [...$f, 'label' => __($f['name'])], self::QUICK_FOODS),
        ];
    }

    /** The day's planned meals (with what you logged for them) and your extras. */
    public static function today(User $user, Carbon $date): array
    {
        $logs = FoodLog::where('user_id', $user->id)->whereDate('date', $date)->orderBy('id')->get();
        $planned = MealPlan::where('household_id', $user->household_id)->whereDate('date', $date)
            ->with('recipe:id,name,name_ta,meal_type,is_veg,calories,protein_g,image_path')->get()
            ->sortBy(fn ($m) => array_search($m->meal_type, Recipe::MEAL_TYPES))->values()
            ->map(fn (MealPlan $m) => [
                'id' => $m->id, 'meal_type' => $m->meal_type, 'recipe' => $m->recipe,
                'log' => $logs->firstWhere('meal_plan_id', $m->id),
            ]);

        return [
            'date' => $date->toDateString(),
            'calories' => round($logs->sum('calories')),
            'protein_g' => round($logs->sum('protein_g'), 1),
            'planned_calories' => round($planned->sum(fn ($m) => (float) $m['recipe']->calories)),
            'planned' => $planned,
            'extras' => $logs->whereNull('meal_plan_id')->values(),
        ];
    }

    /**
     * One row per day from $from to $to.
     *
     * @return array<string, array{date: string, calories: float, protein_g: float, logged: bool, steps: int, on_target: bool, step_goal: bool, protein_hit: bool, plan_followed: bool, extras: int}>
     */
    public static function days(User $user, HealthProfile $p, array $target, Carbon $from, Carbon $to): array
    {
        $logs = FoodLog::where('user_id', $user->id)->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()
            ->groupBy(fn ($l) => $l->date->toDateString());
        $steps = StepLog::where('user_id', $user->id)->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()
            ->mapWithKeys(fn ($s) => [$s->date->toDateString() => $s->steps]);
        $plans = MealPlan::where('household_id', $user->household_id)->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get(['id', 'date'])
            ->groupBy(fn ($m) => $m->date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $dayLogs = $logs[$key] ?? collect();
            $calories = (float) $dayLogs->sum('calories');
            $onTarget = $dayLogs->isNotEmpty() && CalorieTarget::onTarget($p->goal, $calories, $target['calories']);
            $planIds = ($plans[$key] ?? collect())->pluck('id');
            $days[$key] = [
                'date' => $key,
                'calories' => round($calories),
                'protein_g' => round((float) $dayLogs->sum('protein_g'), 1),
                'logged' => $dayLogs->isNotEmpty(),
                'steps' => (int) ($steps[$key] ?? 0),
                'on_target' => $onTarget,
                'step_goal' => ($steps[$key] ?? 0) >= $p->step_goal,
                'protein_hit' => $dayLogs->sum('protein_g') >= $target['protein_g'],
                'plan_followed' => $onTarget && $planIds->isNotEmpty() && $planIds->diff($dayLogs->pluck('meal_plan_id'))->isEmpty(),
                'extras' => $dayLogs->whereNull('meal_plan_id')->count(),
            ];
        }

        return $days;
    }

    /** Days in a row ending on the last day, or the day before when the last day is still in progress. */
    public static function streak(array $days, string $key, bool $lastInProgress = false): int
    {
        $rows = array_values($days);
        $i = count($rows) - 1;
        if ($i >= 0 && ! $rows[$i][$key] && $lastInProgress) {
            $i--;
        }
        $n = 0;
        for (; $i >= 0 && $rows[$i][$key]; $i--) {
            $n++;
        }

        return $n;
    }

    /** @return list<array{key: string, earned_on: ?string}> */
    public static function badges(User $user, HealthProfile $p, array $days, Collection $weights): array
    {
        $earned = ['started' => $p->started_on->toDateString()];
        $run = ['on_target' => 0, 'step_goal' => 0, 'plan_followed' => 0];
        $totals = ['steps' => 0, 'protein' => 0, 'extras' => 0];

        foreach ($days as $date => $d) {
            foreach ($run as $k => $n) {
                $run[$k] = $d[$k] ? $n + 1 : 0;
            }
            $totals['steps'] += $d['steps'];
            $totals['protein'] += $d['protein_hit'] ? 1 : 0;
            $totals['extras'] += $d['extras'];

            $checks = [
                'first_log' => $d['logged'],
                'plan_follower' => $d['plan_followed'],
                'plan_streak_3' => $run['plan_followed'] >= 3,
                'on_target_3' => $run['on_target'] >= 3,
                'on_target_7' => $run['on_target'] >= 7,
                'on_target_21' => $run['on_target'] >= 21,
                'step_goal' => $d['step_goal'],
                'step_streak_7' => $run['step_goal'] >= 7,
                'steps_100k' => $totals['steps'] >= 100_000,
                'protein_5' => $totals['protein'] >= 5,
                'honest_5' => $totals['extras'] >= 5,
            ];
            foreach ($checks as $badge => $ok) {
                if ($ok) {
                    $earned[$badge] ??= $date;
                }
            }
        }

        if ($p->goal !== 'maintain') {
            $all = WeightLog::where('user_id', $user->id)->where('date', '>=', $p->started_on)->orderBy('date')->get();
            foreach ($all as $w) {
                $pr = self::progress($p, $w->weight_kg);
                foreach (['first_kg' => $pr['done_kg'] >= 1, 'halfway' => $pr['percent'] >= 50, 'goal_reached' => $pr['percent'] >= 100] as $badge => $ok) {
                    if ($ok) {
                        $earned[$badge] ??= $w->date->toDateString();
                    }
                }
            }
        }

        return array_values(array_map(
            fn ($key) => ['key' => $key, 'earned_on' => $earned[$key] ?? null],
            array_filter(array_keys(self::BADGES), fn ($k) => $p->goal !== 'maintain' || ! in_array($k, ['first_kg', 'halfway', 'goal_reached'], true)),
        ));
    }

    /** Weight change towards the goal (never negative). Null when maintaining. */
    public static function progress(HealthProfile $p, float $weight): ?array
    {
        if ($p->goal === 'maintain') {
            return null;
        }
        $sign = $p->goal === 'lose' ? 1 : -1;
        $total = abs($p->start_weight_kg - $p->target_weight_kg);
        $done = max(0, round($sign * ($p->start_weight_kg - $weight), 1));

        return [
            'start' => $p->start_weight_kg,
            'current' => $weight,
            'target' => $p->target_weight_kg,
            'done_kg' => $done,
            'percent' => $total > 0 ? (int) min(100, round($done / $total * 100)) : 100,
            'weeks_left' => $p->pace_kg > 0 ? (int) ceil(max(0, $total - $done) / $p->pace_kg) : 0,
        ];
    }

    /** Up to four short, positive tips for the day. */
    public static function tips(HealthProfile $p, array $target, array $today, int $steps, array $streaks, ?array $progress, float $weight, Carbon $date): array
    {
        $tips = [];
        $intake = $today['calories'];
        $goal = $target['calories'];
        $logged = $intake > 0 || $today['extras']->isNotEmpty() || $today['planned']->contains(fn ($m) => $m['log']);
        $isToday = $date->isToday();

        if (! $logged) {
            $tips[] = __('Tick ✓ on each planned meal after you eat it, and add any extras. I’ll keep count for you.');
        } elseif ($intake > $goal * 1.05 && $p->goal !== 'gain') {
            $over = $intake - $goal;
            $walk = CalorieTarget::stepsFor($over, $weight);
            $tips[] = __('You’re :kcal kcal over today — no worries, it happens! A :min-minute walk (about :steps steps) balances it.', [
                'kcal' => (int) round($over), 'min' => (int) ceil($walk / 100), 'steps' => number_format($walk),
            ]);
        } elseif ($p->goal === 'gain' && $intake < $goal * 0.95 && (! $isToday || now()->hour >= 16)) {
            $tips[] = __('You’re :kcal kcal short of your target. A glass of milk with a banana (~250 kcal) or a handful of nuts helps.', ['kcal' => (int) round($goal - $intake)]);
        } elseif (CalorieTarget::onTarget($p->goal, $intake, $goal)) {
            $tips[] = __('Right on track today — well done! 🌟');
        } else {
            $tips[] = __('You have :kcal kcal left for today. Enjoy your meals!', ['kcal' => (int) round($goal - $intake)]);
        }

        if ($streaks['on_target'] >= 2) {
            $tips[] = __('🔥 :n days in a row on target — keep it going!', ['n' => $streaks['on_target']]);
        }

        if ($logged && $today['protein_g'] < $target['protein_g'] * 0.7) {
            $tips[] = __('Protein is :g g short today — curd, eggs, sundal, paneer or dal help.', ['g' => (int) round($target['protein_g'] - $today['protein_g'])]);
        }

        if ($steps >= $p->step_goal) {
            $tips[] = __('Step goal done — :steps steps today! 👟', ['steps' => number_format($steps)]);
        } elseif ($steps > 0) {
            $tips[] = __('Only :n more steps to your goal — a short walk after dinner does it!', ['n' => number_format($p->step_goal - $steps)]);
        } else {
            $tips[] = __('Add your steps from your phone’s step counter to join the :goal-step challenge.', ['goal' => number_format($p->step_goal)]);
        }

        $plannedKcal = $today['planned_calories'];
        if ($plannedKcal > 0 && $p->goal !== 'lose' && $plannedKcal < $goal * 0.8) {
            $tips[] = __('Today’s plan gives about :plan kcal. Take a bigger portion or add a snack to reach :target.', ['plan' => (int) $plannedKcal, 'target' => $goal]);
        } elseif ($plannedKcal > $goal * 1.1 && $p->goal === 'lose') {
            $tips[] = __('Today’s plan is about :plan kcal — a smaller portion of rice keeps you near :target.', ['plan' => (int) $plannedKcal, 'target' => $goal]);
        }

        if ($progress && $progress['done_kg'] >= 0.5) {
            $tips[] = __($p->goal === 'lose' ? 'You’ve lost :kg kg since you started. Great going!' : 'You’ve gained :kg kg since you started. Great going!', ['kg' => $progress['done_kg']]);
        }

        return array_slice($tips, 0, 4);
    }

    /** Free text ("2 vadai and a coffee") -> food rows with estimated calories. A draft; nothing is saved. */
    public static function estimateFood(string $text): array
    {
        $answer = Ai::json(
            'You are a nutritionist who knows Indian home and street food. Estimate calories and protein using standard food composition values.',
            "The user ate: \"{$text}\"\n\nReturn {\"items\": [{\"name\": short name with amount, in the language the user wrote, \"calories\": kcal for the amount eaten, \"protein_g\": grams}]}",
            400,
        );

        $items = [];
        foreach (array_filter((array) ($answer['items'] ?? []), 'is_array') as $row) {
            $kcal = $row['calories'] ?? null;
            $protein = $row['protein_g'] ?? 0;
            $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 120);
            if ($name === '' || ! is_numeric($kcal) || $kcal < 0 || $kcal > 2500 || ! is_numeric($protein) || $protein < 0 || $protein > 150) {
                continue;
            }
            $items[] = ['name' => $name, 'calories' => round((float) $kcal), 'protein_g' => round((float) $protein, 1)];
        }
        if (! $items) {
            throw new AiUnavailable(__('Couldn’t work that out. Try again, or enter the calories yourself.'));
        }

        return $items;
    }
}
