<?php

namespace Tests\Feature;

use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
        $this->travelTo('2026-10-05 09:00');
    }

    private function ing(string $name): int
    {
        return Ingredient::where('name', $name)->value('id');
    }

    private function recipe(string $name, array $rows): Recipe
    {
        $recipe = Recipe::create(['name' => $name, 'meal_type' => 'dinner', 'servings' => 2, 'cook_time' => 20, 'is_veg' => false]);
        foreach ($rows as $r) {
            $recipe->ingredients()->create(['ingredient_id' => $this->ing($r[0]), 'quantity' => $r[1], 'unit' => $r[2]]);
        }

        return $recipe;
    }

    private function stock(string $name, float $qty, string $unit, ?string $expiry = null, array $extra = [], ?User $user = null): PantryItem
    {
        return PantryItem::create([
            'household_id' => ($user ?? $this->user)->household_id, 'ingredient_id' => $this->ing($name),
            'quantity' => $qty, 'unit' => $unit, 'expiry_date' => $expiry, ...$extra,
        ]);
    }

    public function test_use_soon_prefers_nearest_expiry_then_best_match(): void
    {
        // Chicken expires tomorrow, paneer in 3 days.
        $this->stock('Chicken', 500, 'g', '2026-10-06');
        $this->stock('Paneer', 200, 'g', '2026-10-08');
        $this->stock('Onion', 5, 'piece');
        $this->recipe('Chicken Curry', [['Chicken', 500, 'g'], ['Onion', 2, 'piece']]);          // 100%
        $this->recipe('Chicken Biryani', [['Chicken', 500, 'g'], ['Basmati Rice', 300, 'g']]);   // 50%
        $this->recipe('Paneer Bhurji', [['Paneer', 200, 'g'], ['Onion', 1, 'piece']]);           // 100%, later expiry
        $this->recipe('Dal', [['Toor Dal', 200, 'g']]);                                           // no expiring items

        $names = $this->actingAs($this->user)->getJson('/api/cook?filter=use_soon')->json('data.*.recipe.name');

        $this->assertSame(['Chicken Curry', 'Chicken Biryani', 'Paneer Bhurji'], $names);
    }

    public function test_discard_expired_writes_off_only_expired_stock(): void
    {
        $curd = $this->stock('Curd', 400, 'g', '2026-10-03');
        $milk = $this->stock('Milk', 1, 'L', '2026-10-07');

        $this->actingAs($this->user)->postJson('/api/pantry/discard-expired')->assertOk()->assertJsonPath('count', 1);

        $this->assertEquals(0, $curd->fresh()->quantity);
        $this->assertEquals(1, $milk->fresh()->quantity);
        $this->assertDatabaseHas('pantry_transactions', ['pantry_item_id' => $curd->id, 'type' => 'EXPIRED', 'quantity_change' => -400, 'balance_after' => 0]);
    }

    public function test_discard_expired_does_not_touch_other_households(): void
    {
        $theirs = $this->stock('Curd', 400, 'g', '2026-10-03', [], User::factory()->create());

        $this->actingAs($this->user)->postJson('/api/pantry/discard-expired')->assertJsonPath('count', 0);

        $this->assertEquals(400, $theirs->fresh()->quantity);
    }

    public function test_dashboard_summarises_the_kitchen(): void
    {
        $omelette = $this->recipe('Omelette', [['Egg', 2, 'piece']]);
        $curry = $this->recipe('Chicken Curry', [['Chicken', 500, 'g']]);
        $this->stock('Egg', 6, 'piece', null, ['minimum_stock' => 6]);
        $this->stock('Chicken', 100, 'g', '2026-10-06');
        $this->stock('Curd', 100, 'g', '2026-10-01');
        MealPlan::create(['household_id' => $this->user->household_id, 'date' => '2026-10-05', 'meal_type' => 'dinner', 'recipe_id' => $curry->id, 'servings' => 2]);
        MealPlan::create(['household_id' => $this->user->household_id, 'date' => '2026-10-05', 'meal_type' => 'breakfast', 'recipe_id' => $omelette->id, 'servings' => 2]);
        MealPlan::create(['household_id' => $this->user->household_id, 'date' => '2026-10-06', 'meal_type' => 'lunch', 'recipe_id' => $omelette->id, 'servings' => 1]);
        GroceryList::for($this->user->household_id)->items()->createMany([
            ['name' => 'Soap', 'category' => 'Household', 'source' => 'manual'],
            ['name' => 'Salt', 'category' => 'Spices', 'source' => 'manual', 'purchased' => true],
        ]);

        $d = $this->actingAs($this->user)->getJson('/api/dashboard')->assertOk()->json();

        $this->assertSame(['breakfast', 'dinner'], array_column($d['today_meals'], 'meal_type'));
        $this->assertSame([true, false], array_column($d['today_meals'], 'can_cook_now'));
        $this->assertSame(['Omelette'], array_column(array_column($d['cook_now'], 'recipe'), 'name'));
        $this->assertSame(['Chicken Curry'], array_column(array_column($d['use_soon'], 'recipe'), 'name'));
        $this->assertSame(['Chicken'], array_column(array_column($d['expiring'], 'ingredient'), 'name'));
        $this->assertSame(1, $d['expired_count']);
        $this->assertSame(['Egg'], array_column(array_column($d['low_stock'], 'ingredient'), 'name'));
        $this->assertSame(1, $d['grocery_remaining']);
    }

    public function test_dashboard_only_shows_own_household(): void
    {
        $other = User::factory()->create();
        $this->stock('Chicken', 100, 'g', '2026-10-06', ['minimum_stock' => 500], $other);

        $d = $this->actingAs($this->user)->getJson('/api/dashboard')->json();

        $this->assertSame([], $d['expiring']);
        $this->assertSame([], $d['low_stock']);
        $this->assertSame(0, $d['pantry_count']);
    }
}
