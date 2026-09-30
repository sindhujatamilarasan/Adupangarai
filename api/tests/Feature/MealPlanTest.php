<?php

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function recipeId(string $name): int
    {
        return Recipe::where('name', $name)->value('id');
    }

    private function plan(array $overrides = []): array
    {
        return $this->actingAs($this->user)->postJson('/api/meal-plans', [
            'date' => '2026-10-05', 'meal_type' => 'dinner', 'recipe_id' => $this->recipeId('Chicken Curry'), 'servings' => 4, ...$overrides,
        ])->assertCreated()->json('data');
    }

    public function test_add_change_and_remove_a_planned_meal(): void
    {
        $plan = $this->plan();
        $this->assertSame('Chicken Curry', $plan['recipe']['name']);

        $this->patchJson("/api/meal-plans/{$plan['id']}", ['recipe_id' => $this->recipeId('Dal'), 'servings' => 2])
            ->assertOk()->assertJsonPath('data.recipe.name', 'Dal')->assertJsonPath('data.servings', 2);

        $this->deleteJson("/api/meal-plans/{$plan['id']}")->assertOk();
        $this->assertSame(0, MealPlan::count());
    }

    public function test_slot_can_hold_multiple_recipes(): void
    {
        $this->plan(['recipe_id' => $this->recipeId('Chapati')]);
        $this->plan(['recipe_id' => $this->recipeId('Dal')]);

        $this->assertSame(2, MealPlan::where('date', '2026-10-05')->where('meal_type', 'dinner')->count());
    }

    public function test_week_listing_is_limited_to_seven_days(): void
    {
        $this->plan(['date' => '2026-10-05']);
        $this->plan(['date' => '2026-10-11']);
        $this->plan(['date' => '2026-10-12']);

        $res = $this->getJson('/api/meal-plans?start=2026-10-05')->assertOk();

        $this->assertSame('2026-10-11', $res->json('end'));
        $this->assertSame(['2026-10-05', '2026-10-11'], $res->json('data.*.date'));
    }

    public function test_default_week_starts_on_monday(): void
    {
        $this->travelTo('2026-10-08 10:00'); // Thursday

        $this->actingAs($this->user)->getJson('/api/meal-plans')->assertJsonPath('start', '2026-10-05');
        // Tamil locale must not move the week to Sunday.
        $this->getJson('/api/meal-plans', ['Accept-Language' => 'ta'])->assertJsonPath('start', '2026-10-05');
    }

    public function test_validation(): void
    {
        $this->actingAs($this->user)->postJson('/api/meal-plans', [
            'date' => 'tomorrow', 'meal_type' => 'brunch', 'recipe_id' => 999999, 'servings' => 0,
        ])->assertJsonValidationErrors(['date', 'meal_type', 'recipe_id', 'servings']);
    }

    public function test_cannot_plan_another_households_recipe(): void
    {
        $other = User::factory()->create();
        $private = Recipe::create(['household_id' => $other->household_id, 'name' => 'Secret', 'meal_type' => 'dinner', 'servings' => 2, 'is_veg' => true]);

        $this->actingAs($this->user)->postJson('/api/meal-plans', [
            'date' => '2026-10-05', 'meal_type' => 'dinner', 'recipe_id' => $private->id, 'servings' => 2,
        ])->assertJsonValidationErrors('recipe_id');
    }

    public function test_clear_week_removes_uncooked_meals_only_in_range_and_household(): void
    {
        $this->plan(['date' => '2026-10-05']);
        $this->plan(['date' => '2026-10-11']);
        $this->plan(['date' => '2026-10-12']); // next week
        $cooked = MealPlan::create(['household_id' => $this->user->household_id, 'date' => '2026-10-06', 'meal_type' => 'lunch', 'recipe_id' => $this->recipeId('Dal'), 'servings' => 2, 'cooked_at' => now()]);
        $other = User::factory()->create();
        $theirs = MealPlan::create(['household_id' => $other->household_id, 'date' => '2026-10-06', 'meal_type' => 'lunch', 'recipe_id' => $this->recipeId('Dal'), 'servings' => 2]);

        $this->actingAs($this->user)->postJson('/api/meal-plans/clear', ['start' => '2026-10-05', 'end' => '2026-10-11'])
            ->assertOk()->assertJsonPath('count', 2);

        $this->assertNotNull($cooked->fresh());
        $this->assertNotNull($theirs->fresh());
        $this->assertSame(1, MealPlan::where('household_id', $this->user->household_id)->whereDate('date', '2026-10-12')->count());
    }

    public function test_users_cannot_access_another_households_plan(): void
    {
        $plan = $this->plan();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->getJson('/api/meal-plans?start=2026-10-05')->assertJsonCount(0, 'data');
        $this->actingAs($intruder)->patchJson("/api/meal-plans/{$plan['id']}", ['servings' => 9])->assertNotFound();
        $this->actingAs($intruder)->deleteJson("/api/meal-plans/{$plan['id']}")->assertNotFound();
        $this->assertSame(4, MealPlan::find($plan['id'])->servings);
    }
}
