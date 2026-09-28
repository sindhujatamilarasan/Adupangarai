<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_normalize_to_the_same_key(): void
    {
        $this->assertSame('red onion', Ingredient::normalize('  Red   Onions '));
        $this->assertSame('tomato', Ingredient::normalize('TOMATOES'));
        $this->assertSame('egg', Ingredient::normalize('eggs'));
    }

    public function test_seeder_creates_enough_ingredients_and_is_idempotent(): void
    {
        $this->seed(IngredientSeeder::class);
        $this->seed(IngredientSeeder::class);

        $this->assertGreaterThanOrEqual(30, Ingredient::count());
    }

    public function test_creating_a_duplicate_returns_the_existing_ingredient(): void
    {
        $user = User::factory()->create();
        $category = IngredientCategory::create(['name' => 'Vegetables']);
        $payload = ['ingredient_category_id' => $category->id, 'default_unit' => 'piece'];

        $first = $this->actingAs($user)->postJson('/api/ingredients', ['name' => 'Tomato', ...$payload])
            ->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson('/api/ingredients', ['name' => ' tomatoes ', ...$payload])
            ->assertOk()->assertJsonPath('data.id', $first);

        $this->assertSame(1, Ingredient::count());
    }

    public function test_rejects_unknown_unit(): void
    {
        $user = User::factory()->create();
        $category = IngredientCategory::create(['name' => 'Vegetables']);

        $this->actingAs($user)->postJson('/api/ingredients', [
            'name' => 'Okra', 'ingredient_category_id' => $category->id, 'default_unit' => 'handful',
        ])->assertJsonValidationErrors('default_unit');
    }

    public function test_search_and_category_filter(): void
    {
        $this->seed(IngredientSeeder::class);
        $user = User::factory()->create();

        $names = $this->actingAs($user)->getJson('/api/ingredients?search=Dals')->json('data.*.name');
        $this->assertContains('Toor Dal', $names);
        $this->assertNotContains('Chicken', $names);

        $meat = IngredientCategory::where('name', 'Meat & Seafood')->first();
        $this->actingAs($user)->getJson("/api/ingredients?category={$meat->id}")->assertJsonCount(3, 'data');
    }

    public function test_units_endpoint_lists_all_units(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/units')
            ->assertJsonCount(9, 'data')
            ->assertJsonFragment(['value' => 'L', 'dimension' => 'volume']);
    }

    public function test_ingredients_require_auth(): void
    {
        $this->getJson('/api/ingredients')->assertUnauthorized();
    }
}
