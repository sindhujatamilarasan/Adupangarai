<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\PantryTransaction;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookRecipeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Recipe $curry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
        $this->curry = Recipe::create(['name' => 'Chicken Curry', 'meal_type' => 'dinner', 'servings' => 4, 'is_veg' => false]);
        foreach ([['Chicken', 500, 'g'], ['Onion', 2, 'piece'], ['Tomato', 2, 'piece'], ['Coriander Leaves', 10, 'g', true]] as $r) {
            $this->curry->ingredients()->create(['ingredient_id' => $this->ing($r[0]), 'quantity' => $r[1], 'unit' => $r[2], 'optional' => $r[3] ?? false]);
        }
    }

    private function ing(string $name): int
    {
        return Ingredient::where('name', $name)->value('id');
    }

    private function stock(string $name, float $qty, string $unit, ?string $expiry = null, ?User $user = null): PantryItem
    {
        return PantryItem::create([
            'household_id' => ($user ?? $this->user)->household_id, 'ingredient_id' => $this->ing($name), 'quantity' => $qty, 'unit' => $unit, 'expiry_date' => $expiry,
        ]);
    }

    private function qty(string $name): float
    {
        return PantryItem::where('household_id', $this->user->household_id)->where('ingredient_id', $this->ing($name))->value('quantity');
    }

    public function test_preview_shows_deductions_without_changing_pantry(): void
    {
        $this->stock('Chicken', 1, 'kg');
        $this->stock('Onion', 5, 'piece');

        $rows = collect($this->actingAs($this->user)->getJson("/api/recipes/{$this->curry->id}/cook")->assertOk()->json('data'))->keyBy('name');

        $this->assertEquals(0.5, $rows['Chicken']['deduct']); // 500 g expressed in the pantry's kg
        $this->assertSame('kg', $rows['Chicken']['pantry_unit']);
        $this->assertEquals(0.5, $rows['Chicken']['pantry_after']);
        $this->assertEquals(2, $rows['Onion']['deduct']);
        $this->assertEquals(2, $rows['Tomato']['short']);
        $this->assertEquals(1, $this->qty('Chicken'));
        $this->assertSame(0, PantryTransaction::count());
    }

    public function test_cooking_correctly_decreases_pantry(): void
    {
        $this->stock('Chicken', 1, 'kg');
        $this->stock('Onion', 5, 'piece');
        $this->stock('Tomato', 4, 'piece');
        $this->stock('Coriander Leaves', 50, 'g');

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4])->assertOk();

        $this->assertEquals(0.5, $this->qty('Chicken'));
        $this->assertEquals(3, $this->qty('Onion'));
        $this->assertEquals(2, $this->qty('Tomato'));
        $this->assertEquals(40, $this->qty('Coriander Leaves'));
        $this->assertSame(4, PantryTransaction::where('type', 'COOKED')->count());
        $this->assertDatabaseHas('pantry_transactions', ['type' => 'COOKED', 'quantity_change' => -0.5, 'note' => 'Cooked Chicken Curry ×4']);
    }

    public function test_cooking_scaled_servings(): void
    {
        $this->stock('Chicken', 2000, 'g');
        $this->stock('Onion', 10, 'piece');
        $this->stock('Tomato', 10, 'piece');

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 8])->assertOk();

        $this->assertEquals(1000, $this->qty('Chicken'));
        $this->assertEquals(6, $this->qty('Onion'));
    }

    public function test_shortage_deducts_only_what_exists_and_never_goes_negative(): void
    {
        $this->stock('Chicken', 300, 'g');

        $rows = collect($this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4])->json('data'))->keyBy('name');

        $this->assertEquals(0, $this->qty('Chicken'));
        $this->assertEquals(200, $rows['Chicken']['short']);
    }

    public function test_expired_stock_is_not_used(): void
    {
        $this->stock('Tomato', 4, 'piece', now()->subDay()->toDateString());

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4])->assertOk();

        $this->assertEquals(4, $this->qty('Tomato'));
    }

    public function test_cooking_marks_the_planned_meal_cooked(): void
    {
        $plan = MealPlan::create(['household_id' => $this->user->household_id, 'date' => today(), 'meal_type' => 'dinner', 'recipe_id' => $this->curry->id, 'servings' => 4]);

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4, 'meal_plan_id' => $plan->id])->assertOk();

        $this->assertNotNull($plan->fresh()->cooked_at);
    }

    public function test_cannot_mark_another_households_plan_or_touch_their_pantry(): void
    {
        $other = User::factory()->create();
        $theirs = $this->stock('Chicken', 1000, 'g', null, $other);
        $plan = MealPlan::create(['household_id' => $other->household_id, 'date' => today(), 'meal_type' => 'dinner', 'recipe_id' => $this->curry->id, 'servings' => 4]);

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4, 'meal_plan_id' => $plan->id])->assertNotFound();
        $this->actingAs($this->user)->postJson("/api/recipes/{$this->curry->id}/cook", ['servings' => 4])->assertOk();

        $this->assertEquals(1000, $theirs->fresh()->quantity);
        $this->assertNull($plan->fresh()->cooked_at);
    }

    public function test_cannot_cook_another_households_private_recipe(): void
    {
        $other = User::factory()->create();
        $private = Recipe::create(['household_id' => $other->household_id, 'name' => 'Secret', 'meal_type' => 'dinner', 'servings' => 2, 'is_veg' => true]);

        $this->actingAs($this->user)->getJson("/api/recipes/{$private->id}/cook")->assertNotFound();
        $this->actingAs($this->user)->postJson("/api/recipes/{$private->id}/cook", ['servings' => 2])->assertNotFound();
    }
}
