<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shops sell packs: the list buys whole packs, the kitchen gets what really came home, and the Kitchen shows what the plan needs. */
class PackSizeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
        $this->travelTo('2026-11-17 09:00');
    }

    private function ing(string $name): Ingredient
    {
        return Ingredient::where('name', $name)->firstOrFail();
    }

    /** A 2-serving recipe using 2 tsp turmeric, 3 tomatoes and 200 g rice, planned for 2 people. */
    private function plan(string $date = '2026-11-18'): Recipe
    {
        $recipe = Recipe::create(['household_id' => $this->user->household_id, 'name' => 'Test Rice', 'meal_type' => 'lunch', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 10, 'is_veg' => true]);
        foreach ([['Turmeric Powder', 2, 'tsp'], ['Tomato', 3, 'piece'], ['Rice', 200, 'g']] as [$name, $qty, $unit]) {
            $recipe->ingredients()->create(['ingredient_id' => $this->ing($name)->id, 'quantity' => $qty, 'unit' => $unit, 'optional' => false]);
        }
        MealPlan::create(['household_id' => $this->user->household_id, 'date' => $date, 'meal_type' => 'lunch', 'recipe_id' => $recipe->id, 'servings' => 2]);

        return $recipe;
    }

    private function item(array $list, string $name): array
    {
        return collect($list)->firstWhere('name', $name);
    }

    public function test_the_list_buys_whole_packs_and_remembers_what_the_plan_needs(): void
    {
        $this->plan();
        $list = $this->actingAs($this->user)->postJson('/api/grocery/generate')->assertOk()->json('data');

        $turmeric = $this->item($list, 'Turmeric Powder');
        $this->assertEquals(2, $turmeric['need_quantity']);  // the plan needs 2 tsp
        $this->assertSame(1, $turmeric['packs']);            // buy one 100 g packet
        $this->assertEquals(33, $turmeric['quantity']);      // ≈ 33 tsp
        $this->assertSame('100 g', $turmeric['ingredient']['pack_label']);

        $tomato = $this->item($list, 'Tomato');
        $this->assertEquals(3, $tomato['need_quantity']);
        $this->assertSame(1, $tomato['packs']);              // ½ kg ≈ 4 tomatoes
        $this->assertEquals(4, $tomato['quantity']);
    }

    public function test_adding_to_the_kitchen_uses_what_was_really_bought(): void
    {
        $this->plan();
        $list = $this->actingAs($this->user)->postJson('/api/grocery/generate')->json('data');
        $turmeric = $this->item($list, 'Turmeric Powder');
        $rice = $this->item($list, 'Rice');
        foreach ([$turmeric, $rice] as $i) {
            $this->patchJson("/api/grocery/items/{$i['id']}", ['purchased' => true])->assertOk();
        }

        // Bought two turmeric packets (66 tsp); rice as suggested (1 kg).
        $this->postJson('/api/grocery/add-to-pantry', ['item_ids' => [$turmeric['id'], $rice['id']], 'quantities' => [$turmeric['id'] => 66]])->assertOk();

        $stock = PantryItem::where('household_id', $this->user->household_id)->get()->keyBy(fn ($p) => $p->ingredient->name);
        $this->assertEquals(66, $stock['Turmeric Powder']->quantity);
        $this->assertEquals(1000, $stock['Rice']->quantity);

        // Next time the plan needs turmeric, it's already at home.
        $toBuy = collect($this->postJson('/api/grocery/generate')->json('data'))->where('purchased', false);
        $this->assertNull($toBuy->firstWhere('name', 'Turmeric Powder'));
    }

    public function test_kitchen_shows_what_the_plan_needs(): void
    {
        $this->plan();
        $this->plan('2026-12-30'); // outside the next 7 days: ignored
        PantryItem::create(['household_id' => $this->user->household_id, 'ingredient_id' => $this->ing('Rice')->id, 'quantity' => 1000, 'unit' => 'g']);
        PantryItem::create(['household_id' => $this->user->household_id, 'ingredient_id' => $this->ing('Tomato')->id, 'quantity' => 1, 'unit' => 'piece']);

        $r = $this->actingAs($this->user)->getJson('/api/pantry/plan')->assertOk()->json();
        $rows = collect($r['data'])->keyBy('ingredient.name');

        $this->assertSame(1, $r['meals']);
        $this->assertSame(['Turmeric Powder', 'Tomato', 'Rice'], $rows->keys()->all()); // missing, short, enough
        $this->assertSame('missing', $rows['Turmeric Powder']['status']);
        $this->assertSame('short', $rows['Tomato']['status']);
        $this->assertEquals(3, $rows['Tomato']['need']);
        $this->assertEquals(1, $rows['Tomato']['have']);
        $this->assertSame('enough', $rows['Rice']['status']);
        $this->assertSame(['Test Rice'], $rows['Rice']['used_in']);
    }
}
