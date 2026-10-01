<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodLog;
use App\Models\HealthProfile;
use App\Models\MealPlan;
use App\Models\StepLog;
use App\Models\WeightLog;
use App\Support\CalorieTarget;
use App\Support\Coach;
use App\Support\Foods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Personal health coach: goal, daily food / steps / weight logs, and the day's summary. */
class CoachController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today']]);
        $profile = HealthProfile::where('user_id', $request->user()->id)->first();

        return response()->json($profile
            ? Coach::build($request->user(), $profile, $request->date ? Carbon::parse($request->date) : Carbon::today())
            : ['profile' => null]);
    }

    /** Set or change the goal. A new goal (or `restart`) starts a fresh challenge from today's weight. */
    public function saveProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sex' => ['required', Rule::in(array_keys(CalorieTarget::FLOOR))],
            'birth_year' => ['required', 'integer', 'min:'.(now()->year - 90), 'max:'.(now()->year - 18)],
            'height_cm' => ['required', 'numeric', 'between:120,230'],
            'weight_kg' => ['required', 'numeric', 'between:30,250'],
            'activity' => ['required', Rule::in(array_keys(CalorieTarget::ACTIVITY))],
            'goal' => ['required', Rule::in(CalorieTarget::GOALS)],
            'target_weight_kg' => ['required_unless:goal,maintain', 'nullable', 'numeric', 'between:30,250'],
            'pace_kg' => ['required_unless:goal,maintain', 'nullable', 'numeric'],
            'step_goal' => ['required', 'integer', 'between:2000,30000'],
            'restart' => ['boolean'],
        ], ['birth_year.max' => __('The coach is for adults (18+).')]);

        $weight = round((float) $data['weight_kg'], 1);
        $healthy = array_map(fn ($bmi) => CalorieTarget::weightAtBmi($data['height_cm'], $bmi), CalorieTarget::HEALTHY_BMI);
        $target = $data['goal'] === 'maintain' ? $weight : round((float) $data['target_weight_kg'], 1);

        $error = match ($data['goal']) {
            'lose' => $target >= $weight ? __('For weight loss, the target must be below your current weight.')
                : ($target < $healthy[0] ? __('Please pick a target of at least :kg kg — lower is below a healthy weight for your height.', ['kg' => $healthy[0]]) : null),
            'gain' => $target <= $weight ? __('For weight gain, the target must be above your current weight.') : null,
            default => null,
        };
        if ($error) {
            throw ValidationException::withMessages(['target_weight_kg' => $error]);
        }
        if ($data['goal'] !== 'maintain' && ! in_array((float) $data['pace_kg'], CalorieTarget::PACES[$data['goal']], true)) {
            throw ValidationException::withMessages(['pace_kg' => __('Choose one of the suggested paces.')]);
        }

        $user = $request->user();
        $existing = HealthProfile::where('user_id', $user->id)->first();
        $fresh = ! $existing || $existing->goal !== $data['goal'] || ($data['restart'] ?? false);

        $profile = DB::transaction(function () use ($user, $existing, $fresh, $data, $weight, $target) {
            $profile = HealthProfile::updateOrCreate(['user_id' => $user->id], [
                'sex' => $data['sex'], 'birth_year' => $data['birth_year'], 'height_cm' => $data['height_cm'],
                'activity' => $data['activity'], 'goal' => $data['goal'], 'target_weight_kg' => $target,
                'pace_kg' => $data['goal'] === 'maintain' ? 0 : $data['pace_kg'], 'step_goal' => $data['step_goal'],
                'start_weight_kg' => $fresh ? $weight : $existing->start_weight_kg,
                'started_on' => $fresh ? Carbon::today() : $existing->started_on,
            ]);
            WeightLog::updateOrCreate(['user_id' => $user->id, 'date' => Carbon::today()->toDateString()], ['weight_kg' => $weight]);

            return $profile;
        });

        return response()->json(['message' => __($fresh ? 'Your challenge has started. You’ve got this! 💪' : 'Goal updated.'), 'profile' => $profile]);
    }

    public function logWeight(Request $request): JsonResponse
    {
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'between:30,250'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        WeightLog::updateOrCreate(
            ['user_id' => $request->user()->id, 'date' => $data['date'] ?? Carbon::today()->toDateString()],
            ['weight_kg' => round((float) $data['weight_kg'], 1)],
        );

        return response()->json(['message' => __('Weight saved.')]);
    }

    public function setSteps(Request $request): JsonResponse
    {
        $data = $request->validate([
            'steps' => ['required', 'integer', 'between:0,100000'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        StepLog::updateOrCreate(
            ['user_id' => $request->user()->id, 'date' => $data['date'] ?? Carbon::today()->toDateString()],
            ['steps' => $data['steps']],
        );

        return response()->json(['message' => __('Steps saved.')]);
    }

    /** Log food: a planned meal ("I ate it", with a portion) or extras with their calories. */
    public function addFood(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.meal_plan_id' => ['nullable', 'integer', Rule::exists('meal_plans', 'id')->where('household_id', $user->household_id)],
            'items.*.portion' => ['nullable', 'numeric', Rule::in([0.5, 1, 1.5, 2])],
            'items.*.name' => ['required_without:items.*.meal_plan_id', 'nullable', 'string', 'max:120'],
            'items.*.calories' => ['required_without:items.*.meal_plan_id', 'nullable', 'numeric', 'between:0,3000'],
            'items.*.protein_g' => ['nullable', 'numeric', 'between:0,200'],
            'items.*.source' => ['nullable', Rule::in(['quick', 'ai', 'manual'])],
        ]);
        $date = $data['date'] ?? Carbon::today()->toDateString();

        DB::transaction(function () use ($data, $user, $date) {
            foreach ($data['items'] as $item) {
                $portion = (float) ($item['portion'] ?? 1);
                if (! empty($item['meal_plan_id'])) {
                    $plan = MealPlan::with('recipe')->find($item['meal_plan_id']);
                    FoodLog::updateOrCreate(['user_id' => $user->id, 'meal_plan_id' => $plan->id], [
                        'date' => $plan->date->toDateString(), 'name' => $plan->recipe->name, 'portion' => $portion, 'source' => 'plan',
                        'calories' => round((float) $plan->recipe->calories * $portion, 1),
                        'protein_g' => round((float) $plan->recipe->protein_g * $portion, 1),
                    ]);

                    continue;
                }
                FoodLog::create([
                    'user_id' => $user->id, 'date' => $date, 'name' => $item['name'], 'portion' => $portion,
                    'calories' => round((float) $item['calories'] * $portion, 1),
                    'protein_g' => round((float) ($item['protein_g'] ?? 0) * $portion, 1),
                    'source' => $item['source'] ?? 'manual',
                ]);
            }
        });

        return response()->json(['message' => __('Logged. Thanks for being honest — it all counts!')], 201);
    }

    public function deleteFood(Request $request, FoodLog $log): JsonResponse
    {
        abort_unless($log->user_id === $request->user()->id, 404);
        $log->delete();

        return response()->json(['message' => __('Removed.')]);
    }

    public function estimateFood(Request $request): JsonResponse
    {
        $text = $request->validate(['text' => ['required', 'string', 'max:500']])['text'];

        $result = Coach::estimateFood($text);

        return response()->json(['data' => $result['items'], 'source' => $result['source']]);
    }

    /** Search the built-in food list (no AI). */
    public function foods(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:50']]);

        return response()->json(['data' => Foods::search($data['search'] ?? '')]);
    }
}
