<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\HealthProfile;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use App\Support\CalorieTarget;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoachTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
        $this->travelTo('2026-10-05 20:00');
    }

    private function profile(array $overrides = []): array
    {
        return [
            'sex' => 'female', 'birth_year' => 1996, 'height_cm' => 160, 'weight_kg' => 70, 'activity' => 'light',
            'goal' => 'lose', 'target_weight_kg' => 62, 'pace_kg' => 0.5, 'step_goal' => 10000, ...$overrides,
        ];
    }

    private function setUpGoal(array $overrides = []): void
    {
        $this->actingAs($this->user)->putJson('/api/coach/profile', $this->profile($overrides))->assertOk();
    }

    private function coach(?string $date = null): array
    {
        return $this->actingAs($this->user)->getJson('/api/coach'.($date ? "?date=$date" : ''))->assertOk()->json();
    }

    private function eat(float $kcal, string $date, float $protein = 0): void
    {
        $this->actingAs($this->user)->postJson('/api/coach/food', ['date' => $date, 'items' => [['name' => 'Food', 'calories' => $kcal, 'protein_g' => $protein]]])->assertCreated();
    }

    private function badge(array $coach, string $key): ?string
    {
        return collect($coach['badges'])->firstWhere('key', $key)['earned_on'] ?? null;
    }

    public function test_targets_use_mifflin_st_jeor_with_a_safe_floor(): void
    {
        $p = new HealthProfile($this->profile(['start_weight_kg' => 70]));
        $t = CalorieTarget::for($p, 70, 2026);
        // BMR 1389 x 1.375 = 1910 maintenance, minus 550 for 0.5 kg/week.
        $this->assertSame(1389, $t['bmr']);
        $this->assertSame(1360, $t['calories']);
        $this->assertSame(102, $t['protein_g']); // 1.6 g/kg of a healthy weight (BMI 25 = 64 kg)
        $this->assertFalse($t['floored']);

        $small = new HealthProfile($this->profile(['height_cm' => 150, 'activity' => 'sedentary', 'pace_kg' => 0.75]));
        $t = CalorieTarget::for($small, 50, 2026);
        $this->assertSame(1200, $t['calories']);
        $this->assertTrue($t['floored']);

        $gain = new HealthProfile($this->profile(['goal' => 'gain', 'sex' => 'male', 'pace_kg' => 0.25]));
        $this->assertGreaterThan(CalorieTarget::for($gain, 70, 2026)['maintenance'], CalorieTarget::for($gain, 70, 2026)['calories']);
    }

    public function test_goal_setup_rejects_unsafe_or_inconsistent_targets(): void
    {
        $put = fn ($o) => $this->actingAs($this->user)->putJson('/api/coach/profile', $this->profile($o));

        $put(['target_weight_kg' => 75])->assertStatus(422)->assertJsonValidationErrors('target_weight_kg');
        $put(['target_weight_kg' => 45])->assertStatus(422)->assertJsonValidationErrors('target_weight_kg'); // BMI < 18.5 at 160 cm
        $put(['birth_year' => 2012])->assertStatus(422)->assertJsonValidationErrors('birth_year');
        $put(['pace_kg' => 2])->assertStatus(422)->assertJsonValidationErrors('pace_kg');
        $put(['goal' => 'gain', 'target_weight_kg' => 65])->assertStatus(422);

        $this->assertNull($this->coach()['profile']);
        $put([])->assertOk();
        $c = $this->coach();
        $this->assertEquals(70, $c['current_weight']);
        $this->assertSame(1360, $c['target']['calories']);
        $this->assertSame('2026-10-05', $this->badge($c, 'started'));
    }

    public function test_planned_meals_extras_and_positive_over_target_tip(): void
    {
        $this->setUpGoal();
        $recipe = Recipe::whereNull('household_id')->whereNotNull('calories')->first();
        $plan = MealPlan::create(['household_id' => $this->user->household_id, 'date' => '2026-10-05', 'meal_type' => 'lunch', 'recipe_id' => $recipe->id, 'servings' => 2]);

        $this->actingAs($this->user)->postJson('/api/coach/food', ['items' => [['meal_plan_id' => $plan->id, 'portion' => 1.5]]])->assertCreated();
        // Logging it again changes the portion instead of counting it twice.
        $this->actingAs($this->user)->postJson('/api/coach/food', ['items' => [['meal_plan_id' => $plan->id, 'portion' => 2]]])->assertCreated();
        $this->eat(1500, '2026-10-05');

        $c = $this->coach();
        $this->assertEquals(round($recipe->calories * 2 + 1500), $c['today']['calories']);
        $this->assertEquals(2, $c['today']['planned'][0]['log']['portion']);
        $this->assertCount(1, $c['today']['extras']);
        $this->assertStringContainsString('no worries', $c['tips'][0]);
        $this->assertMatchesRegularExpression('/about [\d,]+ steps/', $c['tips'][0]);
    }

    public function test_streaks_and_badges_come_from_the_logs(): void
    {
        $this->travelTo('2026-10-01 09:00');
        $this->setUpGoal();
        foreach (['2026-10-01', '2026-10-02', '2026-10-03'] as $d) {
            $this->travelTo("$d 21:00");
            $this->eat(1300, $d, 110);
            $this->actingAs($this->user)->putJson('/api/coach/steps', ['steps' => 10500])->assertOk();
        }
        $this->travelTo('2026-10-04 21:00');
        $this->eat(2600, '2026-10-04'); // over: breaks the calorie streak, not the steps one
        $this->actingAs($this->user)->putJson('/api/coach/steps', ['steps' => 12000])->assertOk();

        $c = $this->coach('2026-10-04');
        $this->assertSame(0, $c['streaks']['on_target']);
        $this->assertSame(4, $c['streaks']['steps']);
        $this->assertSame('2026-10-01', $this->badge($c, 'first_log'));
        $this->assertSame('2026-10-03', $this->badge($c, 'on_target_3'));
        $this->assertSame('2026-10-01', $this->badge($c, 'step_goal'));
        $this->assertNull($this->badge($c, 'on_target_7'));
        $this->assertNull($this->badge($c, 'protein_5'));

        // Today isn't finished yet, so an empty today doesn't break yesterday's streak.
        $this->travelTo('2026-10-05 08:00');
        $this->assertSame(4, $this->coach()['streaks']['steps']);
    }

    public function test_weight_progress_and_badges(): void
    {
        $this->travelTo('2026-09-01 09:00');
        $this->setUpGoal();
        $this->travelTo('2026-10-05 09:00');
        $this->actingAs($this->user)->postJson('/api/coach/weight', ['weight_kg' => 68.8, 'date' => '2026-09-15'])->assertOk();
        $this->actingAs($this->user)->postJson('/api/coach/weight', ['weight_kg' => 65.9, 'date' => '2026-09-30'])->assertOk();
        $this->actingAs($this->user)->postJson('/api/coach/weight', ['weight_kg' => 60, 'date' => '2026-10-09'])->assertStatus(422);

        $c = $this->coach();
        $this->assertSame(65.9, $c['current_weight']);
        $this->assertSame(4.1, $c['progress']['done_kg']);
        $this->assertSame(51, $c['progress']['percent']);
        $this->assertSame('2026-09-15', $this->badge($c, 'first_kg'));
        $this->assertSame('2026-09-30', $this->badge($c, 'halfway'));
        $this->assertNull($this->badge($c, 'goal_reached'));
        $this->assertLessThan(1360, $c['target']['calories']); // lighter body, smaller target
    }

    public function test_logs_are_private_to_the_person_and_household(): void
    {
        $this->setUpGoal();
        $other = User::factory()->create();
        $theirPlan = MealPlan::create(['household_id' => $other->household_id, 'date' => '2026-10-05', 'meal_type' => 'lunch', 'recipe_id' => Recipe::first()->id, 'servings' => 1]);
        $theirLog = FoodLog::create(['user_id' => $other->id, 'date' => '2026-10-05', 'name' => 'Idli', 'calories' => 100, 'source' => 'manual']);

        $this->actingAs($this->user)->postJson('/api/coach/food', ['items' => [['meal_plan_id' => $theirPlan->id]]])->assertStatus(422);
        $this->actingAs($this->user)->deleteJson("/api/coach/food/{$theirLog->id}")->assertNotFound();
        $this->actingAs($this->user)->getJson('/api/coach?date=2026-10-09')->assertStatus(422); // no logging the future
    }

    public function test_ai_food_estimate_is_checked(): void
    {
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['items' => [
            ['name' => 'Medu vada (2)', 'calories' => 280, 'protein_g' => 8],
            ['name' => 'Impossible', 'calories' => 99999],
            ['calories' => 50],
        ]])]]]])]);

        $r = $this->actingAs($this->user)->postJson('/api/coach/food/estimate', ['text' => '2 vadai'])->assertOk()->json('data');
        $this->assertSame([['name' => 'Medu vada (2)', 'calories' => 280, 'protein_g' => 8]], $r);
        $this->assertSame(0, FoodLog::count());
    }

    public function test_smart_plan_fits_the_calorie_target(): void
    {
        $body = ['start' => '2026-10-06', 'days' => 5, 'meals' => ['breakfast', 'lunch', 'dinner'], 'goal' => 'my_target', 'servings' => 2];
        $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', $body)->assertStatus(422)->assertJsonValidationErrors('goal');

        $this->setUpGoal(['height_cm' => 150, 'activity' => 'sedentary', 'pace_kg' => 0.75, 'weight_kg' => 60, 'target_weight_kg' => 55]);
        $low = $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', $body)->assertOk()->json('summary');

        $this->setUpGoal(['sex' => 'male', 'height_cm' => 185, 'activity' => 'active', 'goal' => 'gain', 'weight_kg' => 70, 'target_weight_kg' => 78]);
        $high = $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', $body)->assertOk()->json('summary');

        $balanced = $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', [...$body, 'goal' => 'balanced'])->json('summary');

        $this->assertSame(1200, $low['target_calories']);
        $this->assertGreaterThan(2500, $high['target_calories']);
        $this->assertEqualsWithDelta(1200, $low['avg_calories'], 120);
        // One serving of our dishes can't reach 2500+, so it picks the most filling ones (the coach suggests bigger portions).
        $this->assertGreaterThan($balanced['avg_calories'], $high['avg_calories']);
    }
}
