<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function id(string $ingredient): int
    {
        return Ingredient::where('name', $ingredient)->value('id');
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Egg Bhurji', 'meal_type' => 'breakfast', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 10, 'is_veg' => false,
            'ingredients' => [
                ['ingredient_id' => $this->id('Egg'), 'quantity' => 4, 'unit' => 'piece'],
                ['ingredient_id' => $this->id('Onion'), 'quantity' => 1, 'unit' => 'piece'],
                ['ingredient_id' => $this->id('Coriander Leaves'), 'quantity' => 5, 'unit' => 'g', 'optional' => true],
            ],
            'steps' => ['Beat eggs.', 'Scramble with onion.'],
            ...$overrides,
        ];
    }

    public function test_seeded_recipes_are_visible_to_every_household(): void
    {
        $names = $this->actingAs($this->user)->getJson('/api/recipes')->assertOk()->json('data.*.name');

        $this->assertGreaterThanOrEqual(10, count($names));
        foreach (['Omelette', 'Chicken Curry', 'Curd Rice', 'Upma', 'Paneer Butter Masala'] as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_show_scales_quantities_to_requested_servings(): void
    {
        $recipe = Recipe::where('name', 'Egg Fried Rice')->first(); // 2 servings

        $data = $this->actingAs($this->user)->getJson("/api/recipes/{$recipe->id}?servings=4")->assertOk()->json('data');

        $this->assertSame(4, $data['requested_servings']);
        $rows = collect($data['ingredients'])->keyBy('ingredient.name');
        $this->assertEquals(400, $rows['Basmati Rice']['quantity']);
        $this->assertEquals(6, $rows['Egg']['quantity']);
        $this->assertSame('g', $rows['Basmati Rice']['unit']);
        $this->assertCount(5, $data['steps']);
    }

    public function test_show_defaults_to_recipe_servings(): void
    {
        $recipe = Recipe::where('name', 'Chicken Curry')->first();

        $data = $this->actingAs($this->user)->getJson("/api/recipes/{$recipe->id}")->json('data');

        $this->assertSame(4, $data['requested_servings']);
        $this->assertEquals(500, collect($data['ingredients'])->firstWhere('ingredient.name', 'Chicken')['quantity']);
    }

    public function test_filters(): void
    {
        $get = fn ($q) => $this->actingAs($this->user)->getJson("/api/recipes?$q")->json('data');

        $this->assertTrue(collect($get('meal_type=breakfast'))->every(fn ($r) => $r['meal_type'] === 'breakfast'));
        $this->assertTrue(collect($get('max_time=30'))->every(fn ($r) => $r['total_time'] <= 30));
        $this->assertNotContains('Chicken Curry', array_column($get('veg=1'), 'name'));
        $this->assertSame(['Chicken Curry', 'Chicken Fried Rice'], array_column($get('search=chicken'), 'name'));
    }

    public function test_create_update_and_delete_own_recipe(): void
    {
        $id = $this->actingAs($this->user)->postJson('/api/recipes', $this->payload())->assertCreated()->json('data.id');

        $data = $this->getJson("/api/recipes/$id")->json('data');
        $this->assertTrue($data['is_editable']);
        $this->assertTrue(collect($data['ingredients'])->firstWhere('ingredient.name', 'Coriander Leaves')['optional']);

        $this->putJson("/api/recipes/$id", $this->payload(['name' => 'Egg Podimas', 'steps' => ['One step.']]))->assertOk();
        $this->assertSame('Egg Podimas', Recipe::find($id)->name);
        $this->assertSame(1, Recipe::find($id)->steps()->count());

        $this->deleteJson("/api/recipes/$id")->assertOk();
        $this->assertNull(Recipe::find($id));
    }

    public function test_recipe_unit_must_match_ingredient_dimension(): void
    {
        $this->actingAs($this->user)->postJson('/api/recipes', $this->payload([
            'ingredients' => [['ingredient_id' => $this->id('Chicken'), 'quantity' => 2, 'unit' => 'piece']],
        ]))->assertJsonValidationErrors('ingredients.0.unit');

        $this->postJson('/api/recipes', $this->payload([
            'ingredients' => [['ingredient_id' => $this->id('Chicken'), 'quantity' => 0.5, 'unit' => 'kg']],
        ]))->assertCreated();
    }

    public function test_rejects_duplicate_ingredients_and_zero_quantity(): void
    {
        $egg = $this->id('Egg');
        $this->actingAs($this->user)->postJson('/api/recipes', $this->payload([
            'ingredients' => [
                ['ingredient_id' => $egg, 'quantity' => 2, 'unit' => 'piece'],
                ['ingredient_id' => $egg, 'quantity' => 0, 'unit' => 'piece'],
            ],
        ]))->assertJsonValidationErrors(['ingredients.0.ingredient_id', 'ingredients.1.quantity']);
    }

    public function test_builtin_recipes_are_read_only(): void
    {
        $recipe = Recipe::where('name', 'Omelette')->first();

        $this->actingAs($this->user)->putJson("/api/recipes/{$recipe->id}", $this->payload())->assertNotFound();
        $this->deleteJson("/api/recipes/{$recipe->id}")->assertNotFound();
        $this->assertFalse($this->getJson("/api/recipes/{$recipe->id}")->json('data.is_editable'));
    }

    public function test_users_cannot_access_another_households_recipe(): void
    {
        $id = $this->actingAs($this->user)->postJson('/api/recipes', $this->payload())->json('data.id');
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->getJson("/api/recipes/$id")->assertNotFound();
        $this->actingAs($intruder)->putJson("/api/recipes/$id", $this->payload(['name' => 'Hacked']))->assertNotFound();
        $this->actingAs($intruder)->deleteJson("/api/recipes/$id")->assertNotFound();
        $this->assertNotContains('Egg Bhurji', $this->actingAs($intruder)->getJson('/api/recipes')->json('data.*.name'));
        $this->assertSame('Egg Bhurji', Recipe::find($id)->name);
    }

    public function test_pantry_rejects_unit_incompatible_with_ingredient(): void
    {
        $this->actingAs($this->user)->postJson('/api/pantry', [
            'ingredient_id' => $this->id('Chicken'), 'quantity' => 2, 'unit' => 'piece',
        ])->assertJsonValidationErrors('unit');
    }
}
