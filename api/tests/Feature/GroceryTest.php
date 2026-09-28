<?php

namespace Tests\Feature;

use App\Models\GroceryItem;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\PantryTransaction;
use App\Models\Recipe;
use App\Models\User;
use App\Support\GroceryCalculator;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroceryTest extends TestCase
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

    private function recipe(string $name, int $servings, array $rows): Recipe
    {
        $recipe = Recipe::create(['name' => $name, 'meal_type' => 'dinner', 'servings' => $servings, 'is_veg' => false]);
        foreach ($rows as $r) {
            $recipe->ingredients()->create(['ingredient_id' => $this->ing($r[0]), 'quantity' => $r[1], 'unit' => $r[2], 'optional' => $r[3] ?? false]);
        }

        return $recipe;
    }

    private function plan(Recipe $recipe, int $servings, string $date = '2026-10-06', array $extra = []): void
    {
        MealPlan::create(['household_id' => $this->user->household_id, 'date' => $date, 'meal_type' => 'dinner', 'recipe_id' => $recipe->id, 'servings' => $servings, ...$extra]);
    }

    private function stock(string $name, float $qty, string $unit, ?string $expiry = null): void
    {
        PantryItem::create(['household_id' => $this->user->household_id, 'ingredient_id' => $this->ing($name), 'quantity' => $qty, 'unit' => $unit, 'expiry_date' => $expiry]);
    }

    private function generate(array $body = []): array
    {
        $items = $this->actingAs($this->user)->postJson('/api/grocery/generate', $body)->assertOk()->json('data');

        // Unpurchased items only; order-insensitive via assertEquals.
        return collect($items)->where('purchased', false)->mapWithKeys(fn ($i) => [$i['name'] => $i['quantity'].' '.$i['unit']])->all();
    }

    public function test_spec_example_chicken_and_eggs(): void
    {
        // Meals need chicken 1500g and 10 eggs; pantry has 500g and 4.
        $this->plan($this->recipe('Chicken Curry', 4, [['Chicken', 500, 'g']]), 4, '2026-10-06');
        $this->plan($this->recipe('Chicken Fry', 2, [['Chicken', 1, 'kg']]), 2, '2026-10-07');
        $this->plan($this->recipe('Omelette', 1, [['Egg', 2, 'piece']]), 5, '2026-10-08');
        $this->stock('Chicken', 500, 'g');
        $this->stock('Egg', 4, 'piece');

        $this->assertEquals(['Chicken' => '1000 g', 'Egg' => '6 piece'], $this->generate());
    }

    public function test_duplicate_requirements_are_aggregated_before_subtraction(): void
    {
        $rice = $this->recipe('Tomato Rice', 2, [['Rice', 200, 'g']]);
        $this->plan($rice, 2, '2026-10-06');
        $this->plan($rice, 4, '2026-10-07');
        $this->stock('Rice', 0.5, 'kg');

        $this->assertEquals(['Rice' => '100 g'], $this->generate()); // 200 + 400 - 500
    }

    public function test_items_fully_covered_by_pantry_are_not_listed(): void
    {
        $this->plan($this->recipe('Omelette', 1, [['Egg', 2, 'piece']]), 1);
        $this->stock('Egg', 12, 'piece');

        $this->assertEquals([], $this->generate());
    }

    public function test_expired_pantry_stock_is_not_subtracted(): void
    {
        $this->plan($this->recipe('Curd Rice', 2, [['Curd', 300, 'g']]), 2);
        $this->stock('Curd', 500, 'g', '2026-10-01');

        $this->assertEquals(['Curd' => '300 g'], $this->generate());
    }

    public function test_never_subtracts_incompatible_units(): void
    {
        $recipe = $this->recipe('Curry', 1, [['Chicken', 500, 'g']])->load('ingredients.ingredient');
        $plan = new MealPlan(['servings' => 1]);
        $plan->setRelation('recipe', $recipe);
        // A pantry row in pieces can't be compared with grams (can't normally be created; guard anyway).
        $pantry = collect([$this->ing('Chicken') => new PantryItem(['quantity' => 3, 'unit' => 'piece'])]);

        $toBuy = GroceryCalculator::shortfall(GroceryCalculator::requirements(collect([$plan])), $pantry, collect());

        $this->assertEquals(500, $toBuy[0]['quantity']);
    }

    public function test_piece_quantities_round_up_and_optional_items_are_skipped(): void
    {
        $this->plan($this->recipe('Omelette', 2, [['Onion', 1, 'piece'], ['Coriander Leaves', 5, 'g', true]]), 3);

        $this->assertEquals(['Onion' => '2 piece'], $this->generate()); // 1.5 onions -> buy 2
    }

    public function test_only_uncooked_meals_in_range_are_used(): void
    {
        $r = $this->recipe('Omelette', 1, [['Egg', 2, 'piece']]);
        $this->plan($r, 1, '2026-10-06');
        $this->plan($r, 1, '2026-10-07', ['cooked_at' => now()]);
        $this->plan($r, 1, '2026-10-20');

        $this->assertEquals(['Egg' => '2 piece'], $this->generate());
        $this->assertEquals(['Egg' => '4 piece'], $this->generate(['start' => '2026-10-06', 'end' => '2026-10-20']));
    }

    public function test_regenerate_keeps_manual_and_purchased_items_and_does_not_double_count(): void
    {
        $this->plan($this->recipe('Omelette', 1, [['Egg', 2, 'piece']]), 5); // 10 eggs
        $this->generate();
        $egg = GroceryItem::where('name', 'Egg')->first();
        $egg->update(['purchased' => true, 'actual_quantity' => 6]);
        $this->actingAs($this->user)->postJson('/api/grocery/items', ['name' => 'Soap'])->assertCreated();

        $items = $this->generate();

        $this->assertEquals(['Egg' => '4 piece', 'Soap' => ' '], $items); // 10 needed - 6 already bought
        $this->assertTrue($egg->fresh()->purchased);
    }

    public function test_manual_items(): void
    {
        $this->actingAs($this->user)->postJson('/api/grocery/items', ['name' => 'Toothpaste'])
            ->assertCreated()->assertJsonPath('data.category', 'Household')->assertJsonPath('data.ingredient_id', null);

        $this->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Milk'), 'quantity' => 1, 'unit' => 'L'])
            ->assertCreated()->assertJsonPath('data.category', 'Dairy & Eggs')->assertJsonPath('data.name', 'Milk');

        $this->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Milk'), 'quantity' => 1, 'unit' => 'kg'])
            ->assertJsonValidationErrors('unit');

        $this->postJson('/api/grocery/items', ['name' => ' tomatoes '])
            ->assertJsonPath('data.ingredient_id', $this->ing('Tomato'))->assertJsonPath('data.unit', 'piece');
    }

    public function test_grocery_purchase_correctly_increases_pantry(): void
    {
        $this->stock('Chicken', 500, 'g');
        $list = $this->actingAs($this->user);
        $chicken = $list->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Chicken'), 'quantity' => 1, 'unit' => 'kg'])->json('data.id');
        $eggs = $list->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Egg'), 'quantity' => 12, 'unit' => 'piece'])->json('data.id');

        $this->patchJson("/api/grocery/items/$chicken", ['purchased' => true, 'actual_quantity' => 1.2, 'price' => 280])->assertOk();
        $this->patchJson("/api/grocery/items/$eggs", ['purchased' => true, 'price' => 84])->assertOk();

        // Nothing changes in the pantry until confirmed.
        $this->assertEquals(500, PantryItem::where('ingredient_id', $this->ing('Chicken'))->value('quantity'));

        $res = $this->postJson('/api/grocery/add-to-pantry', ['item_ids' => [$chicken, $eggs]])->assertOk();

        $this->assertEquals(1700, PantryItem::where('ingredient_id', $this->ing('Chicken'))->value('quantity')); // 500g + 1.2kg
        $this->assertEquals(12, PantryItem::where('ingredient_id', $this->ing('Egg'))->value('quantity'));
        $this->assertSame(2, PantryTransaction::where('type', 'PURCHASE')->count());
        $this->assertSame(0, $res->json('summary.to_add_to_pantry'));
        $this->assertEquals(364, $res->json('summary.spent'));

        // Can't be added twice.
        $this->postJson('/api/grocery/add-to-pantry', ['item_ids' => [$chicken]])->assertJsonValidationErrors('item_ids');
        $this->assertEquals(1700, PantryItem::where('ingredient_id', $this->ing('Chicken'))->value('quantity'));
    }

    public function test_add_to_pantry_is_all_or_nothing(): void
    {
        $list = $this->actingAs($this->user);
        $bought = $list->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Egg'), 'quantity' => 6, 'unit' => 'piece'])->json('data.id');
        $notBought = $list->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Milk'), 'quantity' => 1, 'unit' => 'L'])->json('data.id');
        $this->patchJson("/api/grocery/items/$bought", ['purchased' => true]);

        $this->postJson('/api/grocery/add-to-pantry', ['item_ids' => [$bought, $notBought]])->assertJsonValidationErrors('item_ids');

        $this->assertSame(0, PantryItem::count());
    }

    public function test_clear_purchased_keeps_food_not_yet_in_pantry(): void
    {
        $list = $this->actingAs($this->user);
        $soap = $list->postJson('/api/grocery/items', ['name' => 'Soap'])->json('data.id');
        $egg = $list->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Egg'), 'quantity' => 6, 'unit' => 'piece'])->json('data.id');
        $this->patchJson("/api/grocery/items/$soap", ['purchased' => true]);
        $this->patchJson("/api/grocery/items/$egg", ['purchased' => true]);

        $names = $this->postJson('/api/grocery/clear-purchased')->json('data.*.name');

        $this->assertSame(['Egg'], $names);
    }

    public function test_users_cannot_access_another_households_grocery_items(): void
    {
        $id = $this->actingAs($this->user)->postJson('/api/grocery/items', ['ingredient_id' => $this->ing('Egg'), 'quantity' => 6, 'unit' => 'piece'])->json('data.id');
        $this->patchJson("/api/grocery/items/$id", ['purchased' => true]);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->getJson('/api/grocery')->assertJsonCount(0, 'data');
        $this->actingAs($intruder)->patchJson("/api/grocery/items/$id", ['price' => 1])->assertNotFound();
        $this->actingAs($intruder)->deleteJson("/api/grocery/items/$id")->assertNotFound();
        $this->actingAs($intruder)->postJson('/api/grocery/add-to-pantry', ['item_ids' => [$id]])->assertNotFound();
        $this->assertSame(0, PantryItem::count());
    }
}
