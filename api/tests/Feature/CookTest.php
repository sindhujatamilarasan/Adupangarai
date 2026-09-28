<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RecipeMatcher;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
    }

    private function ing(string $name): int
    {
        return Ingredient::where('name', $name)->value('id');
    }

    /** @param array<array{0:string,1:float,2:string,3?:bool}> $rows */
    private function recipe(string $name, int $servings, array $rows, array $attrs = []): Recipe
    {
        $recipe = Recipe::create([
            'name' => $name, 'meal_type' => 'dinner', 'servings' => $servings, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => false, ...$attrs,
        ]);
        foreach ($rows as $r) {
            $recipe->ingredients()->create(['ingredient_id' => $this->ing($r[0]), 'quantity' => $r[1], 'unit' => $r[2], 'optional' => $r[3] ?? false]);
        }

        return $recipe->load('ingredients.ingredient');
    }

    private function stock(string $name, float $qty, string $unit, ?string $expiry = null, ?User $user = null): void
    {
        PantryItem::create([
            'household_id' => ($user ?? $this->user)->household_id, 'ingredient_id' => $this->ing($name),
            'quantity' => $qty, 'unit' => $unit, 'expiry_date' => $expiry,
        ]);
    }

    private function match(Recipe $recipe, ?int $servings = null): array
    {
        return RecipeMatcher::match($recipe, RecipeMatcher::pantryFor($this->user->household_id), $servings);
    }

    public function test_500g_required_300g_in_pantry_is_200g_missing(): void
    {
        $curry = $this->recipe('Chicken Curry', 4, [['Chicken', 500, 'g']]);
        $this->stock('Chicken', 300, 'g');

        $m = $this->match($curry);

        $this->assertCount(1, $m['insufficient']);
        $this->assertEquals(200, $m['insufficient'][0]['short']);
        $this->assertEquals(300, $m['insufficient'][0]['have']);
        $this->assertSame(60, $m['match_percent']);
        $this->assertSame([], $m['missing']);
    }

    public function test_pantry_in_kg_is_converted_to_recipe_grams(): void
    {
        $curry = $this->recipe('Chicken Curry', 4, [['Chicken', 500, 'g']]);
        $this->stock('Chicken', 0.3, 'kg');

        $this->assertEquals(200, $this->match($curry)['insufficient'][0]['short']);
    }

    public function test_fully_available_recipe(): void
    {
        $omelette = $this->recipe('Omelette', 1, [['Egg', 2, 'piece'], ['Cooking Oil', 1, 'tbsp']]);
        $this->stock('Egg', 6, 'piece');
        $this->stock('Cooking Oil', 1, 'L');

        $m = $this->match($omelette);

        $this->assertSame(RecipeMatcher::AVAILABLE, $m['status']);
        $this->assertSame(100, $m['match_percent']);
        $this->assertCount(2, $m['available']);
    }

    public function test_scaled_servings_change_what_is_missing(): void
    {
        $omelette = $this->recipe('Omelette', 1, [['Egg', 2, 'piece']]);
        $this->stock('Egg', 3, 'piece');

        $this->assertSame(RecipeMatcher::AVAILABLE, $this->match($omelette)['status']);
        $m = $this->match($omelette, 2);
        $this->assertEquals(4, $m['insufficient'][0]['need']);
        $this->assertEquals(1, $m['insufficient'][0]['short']);
    }

    public function test_expired_stock_is_not_usable(): void
    {
        $recipe = $this->recipe('Curd Rice', 2, [['Curd', 300, 'g']]);
        $this->stock('Curd', 500, 'g', now()->subDay()->toDateString());

        $m = $this->match($recipe);

        $this->assertCount(1, $m['missing']);
        $this->assertSame(0, $m['match_percent']);
    }

    public function test_optional_ingredients_do_not_reduce_match(): void
    {
        $recipe = $this->recipe('Omelette', 1, [['Egg', 2, 'piece'], ['Coriander Leaves', 5, 'g', true]]);
        $this->stock('Egg', 2, 'piece');

        $m = $this->match($recipe);

        $this->assertSame(RecipeMatcher::AVAILABLE, $m['status']);
        $this->assertSame(100, $m['match_percent']);
        $this->assertSame('Coriander Leaves', $m['optional_missing'][0]['name']);
    }

    public function test_almost_available_status(): void
    {
        $recipe = $this->recipe('Tomato Rice', 3, [['Rice', 300, 'g'], ['Tomato', 4, 'piece'], ['Onion', 1, 'piece'], ['Salt', 6, 'g']]);
        $this->stock('Rice', 1, 'kg');
        $this->stock('Tomato', 2, 'piece');
        $this->stock('Salt', 1, 'kg');

        $m = $this->match($recipe); // rice 1 + tomato .5 + onion 0 + salt 1 = 2.5/4

        $this->assertSame(62, $m['match_percent']);
        $this->assertSame(RecipeMatcher::ALMOST, $m['status']);
    }

    public function test_cook_endpoint_filters_and_ranks(): void
    {
        $this->recipe('Omelette', 1, [['Egg', 2, 'piece']], ['meal_type' => 'breakfast', 'cook_time' => 5]);
        $this->recipe('Chicken Curry', 4, [['Chicken', 500, 'g']]);
        $this->recipe('Paneer Bhurji', 2, [['Paneer', 200, 'g'], ['Onion', 1, 'piece'], ['Tomato', 1, 'piece']]);
        $this->stock('Egg', 6, 'piece');
        $this->stock('Chicken', 300, 'g', now()->addDay()->toDateString());

        $get = fn ($q = '') => $this->actingAs($this->user)->getJson("/api/cook?$q")->assertOk()->json('data');

        $this->assertSame(['Omelette', 'Chicken Curry', 'Paneer Bhurji'], array_column(array_column($get(), 'recipe'), 'name'));
        $this->assertSame(['Omelette'], array_column(array_column($get('filter=available'), 'recipe'), 'name'));
        $this->assertSame(['Chicken Curry'], array_column(array_column($get('filter=almost'), 'recipe'), 'name'));
        $this->assertSame(['Omelette'], array_column(array_column($get('meal_type=breakfast'), 'recipe'), 'name'));
        $this->assertSame(['Omelette'], array_column(array_column($get('max_time=15'), 'recipe'), 'name'));

        $soon = $get('filter=use_soon');
        $this->assertSame(['Chicken Curry'], array_column(array_column($soon, 'recipe'), 'name'));
        $this->assertSame(1, $soon[0]['match']['uses_expiring'][0]['days_to_expiry']);
    }

    public function test_recipe_detail_includes_match_for_requested_servings(): void
    {
        $curry = $this->recipe('Chicken Curry', 4, [['Chicken', 500, 'g']]);
        $this->stock('Chicken', 300, 'g');

        $m = $this->actingAs($this->user)->getJson("/api/recipes/{$curry->id}?servings=8")->json('data.match');

        $this->assertEquals(700, $m['insufficient'][0]['short']);
    }

    public function test_match_only_uses_own_households_pantry(): void
    {
        $omelette = $this->recipe('Omelette', 1, [['Egg', 2, 'piece']]);
        $this->stock('Egg', 12, 'piece', null, User::factory()->create());

        $this->assertSame(RecipeMatcher::UNAVAILABLE, $this->match($omelette)['status']);
        $this->actingAs($this->user)->getJson('/api/cook?filter=available')->assertJsonCount(0, 'data');
    }
}
