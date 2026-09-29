<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\PantryTransaction;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function fakeAi(array $answer): void
    {
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($answer)]]]])]);
    }

    private function ing(string $name): int
    {
        return Ingredient::where('name', $name)->value('id');
    }

    public function test_pantry_parse_maps_names_units_and_flags_problems_without_saving(): void
    {
        $this->fakeAi(['items' => [
            ['name' => 'Chicken', 'quantity' => 1, 'unit' => 'kilo', 'expiry_days' => 2],
            ['name' => 'eggs', 'quantity' => 1, 'unit' => 'dozen'],
            ['name' => 'Milk', 'quantity' => 2, 'unit' => 'litres'],
            ['name' => 'Curd', 'quantity' => 12, 'unit' => 'dozen'],
            ['name' => 'Dragon fruit', 'quantity' => 1, 'unit' => 'piece'],
            ['name' => 'Rice', 'quantity' => 2, 'unit' => 'piece'],
            ['name' => 'Onion', 'unit' => 'kg'],
        ]]);

        $res = $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'some chicken, eggs, milk and stuff'])->assertOk();
        $this->assertSame('ai', $res->json('source'));
        $rows = collect($res->json('data'))->keyBy('heard');

        $this->assertSame(['ingredient_id' => $this->ing('Chicken'), 'quantity' => 1, 'unit' => 'kg', 'expiry_days' => 2, 'problem' => null],
            collect($rows['Chicken'])->only(['ingredient_id', 'quantity', 'unit', 'expiry_days', 'problem'])->all());
        $this->assertEquals(12, $rows['eggs']['quantity']);
        $this->assertSame('Egg', $rows['eggs']['name']);
        $this->assertSame('L', $rows['Milk']['unit']);
        $this->assertEquals(12, $rows['Curd']['quantity']); // "12 dozen" slip is not turned into 144
        $this->assertSame('unknown_ingredient', $rows['Dragon fruit']['problem']);
        $this->assertSame('unit_mismatch', $rows['Rice']['problem']);
        $this->assertSame('g', $rows['Rice']['unit']); // falls back to the ingredient's unit for the user to fix
        $this->assertSame('no_quantity', $rows['Onion']['problem']);
        $this->assertSame(0, PantryItem::count());
    }

    public function test_messy_model_output_is_cleaned_and_guesses_are_flagged(): void
    {
        $this->fakeAi(['items' => [
            ['name' => '2 tablespoons oil'],
            ['name' => '1/2 teaspoon turmeric'],
            ['name' => 'fresh coriander leaves', 'quantity' => 10, 'unit' => 'g'],
            ['name' => 'Onions', 'quantity' => 2, 'unit' => 'pieces'],
            ['name' => 'xyz', 'quantity' => 1, 'unit' => 'g'],
        ]]);

        $rows = $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'oil, turmeric, coriander and so on'])->json('data');

        $this->assertSame(['Cooking Oil', 'Turmeric Powder', 'Coriander Leaves', 'Onion', 'xyz'], array_column($rows, 'name'));
        $this->assertEquals([2, 0.5, 10, 2, 1], array_column($rows, 'quantity'));
        $this->assertSame(['tbsp', 'tsp', 'g', 'piece', null], array_column($rows, 'unit'));
        $this->assertSame(['guessed', 'guessed', 'guessed', null, 'unknown_ingredient'], array_column($rows, 'problem'));
    }

    public function test_simple_lists_are_read_without_ai(): void
    {
        Http::fake();

        $res = $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'I bought 1 kg chicken, a dozen eggs and 2 litres of milk'])->assertOk();

        $this->assertSame('rules', $res->json('source'));
        $this->assertSame(['Chicken', 'Egg', 'Milk'], $res->json('data.*.name'));
        $this->assertEquals([1, 12, 2], $res->json('data.*.quantity'));
        Http::assertNothingSent();
    }

    public function test_confirmed_bulk_add_updates_pantry_atomically(): void
    {
        $this->actingAs($this->user)->postJson('/api/pantry/bulk', ['type' => 'PURCHASE', 'items' => [
            ['ingredient_id' => $this->ing('Chicken'), 'quantity' => 1, 'unit' => 'kg', 'expiry_date' => now()->addDays(2)->toDateString()],
            ['ingredient_id' => $this->ing('Egg'), 'quantity' => 12, 'unit' => 'piece'],
        ]])->assertOk();

        $this->assertSame(2, PantryItem::count());
        $this->assertSame(2, PantryTransaction::where('type', 'PURCHASE')->count());

        // One bad row -> nothing saved.
        $this->postJson('/api/pantry/bulk', ['type' => 'ADD', 'items' => [
            ['ingredient_id' => $this->ing('Milk'), 'quantity' => 1, 'unit' => 'L'],
            ['ingredient_id' => $this->ing('Rice'), 'quantity' => 2, 'unit' => 'piece'],
        ]])->assertJsonValidationErrors('unit');
        $this->assertSame(0, PantryItem::where('ingredient_id', $this->ing('Milk'))->count());
    }

    public function test_recipe_parse_returns_a_checked_draft(): void
    {
        $this->fakeAi([
            'name' => 'Egg Curry', 'description' => 'Eggs in masala', 'meal_type' => 'brunch', 'cuisine' => 'Tamil',
            'servings' => 500, 'prep_time' => 10, 'cook_time' => 'twenty', 'is_veg' => false,
            'ingredients' => [
                ['name' => 'Eggs', 'quantity' => 4, 'unit' => 'pieces'],
                ['name' => 'Onion', 'quantity' => 2, 'unit' => 'nos', 'optional' => false],
                ['name' => 'Kasuri methi', 'quantity' => 1, 'unit' => 'tsp', 'optional' => true],
            ],
            'steps' => ['Boil eggs.', '', 'Make masala.'],
            'nutrition' => ['calories' => 250, 'protein_g' => 14, 'carbs_g' => 10, 'fat_g' => 17, 'fiber_g' => 2],
        ]);

        $d = $this->actingAs($this->user)->postJson('/api/ai/recipe-parse', ['text' => 'Egg curry...'])->assertOk()->json('data');

        $this->assertSame('lunch', $d['meal_type']);     // invalid meal type replaced
        $this->assertSame(100, $d['servings']);          // clamped
        $this->assertSame(20, $d['cook_time']);          // non-numeric -> default
        $this->assertSame(['Boil eggs.', 'Make masala.'], $d['steps']);
        $this->assertSame($this->ing('Egg'), $d['ingredients'][0]['ingredient_id']);
        $this->assertSame('unknown_ingredient', $d['ingredients'][2]['problem']);
        $this->assertTrue($d['ingredients'][2]['optional']);
        $this->assertEquals(14, $d['protein_g']);
        $this->assertSame(0, Recipe::whereNotNull('household_id')->count());
    }

    public function test_recipe_with_nutrition_can_be_saved_from_a_draft(): void
    {
        $id = $this->actingAs($this->user)->postJson('/api/recipes', [
            'name' => 'Egg Curry', 'meal_type' => 'lunch', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => false,
            'ingredients' => [['ingredient_id' => $this->ing('Egg'), 'quantity' => 4, 'unit' => 'piece']],
            'calories' => 250, 'protein_g' => 14, 'carbs_g' => 10, 'fat_g' => 17, 'fiber_g' => 2,
        ])->assertCreated()->json('data.id');

        $this->assertSame(['low_calorie'], Recipe::find($id)->health_tags);
    }

    public function test_meal_plan_keeps_only_valid_recipe_ids_and_saves_nothing(): void
    {
        $dal = Recipe::where('name', 'Dal')->value('id');
        $upma = Recipe::where('name', 'Upma')->value('id');
        $secret = Recipe::create(['household_id' => User::factory()->create()->household_id, 'name' => 'Secret', 'meal_type' => 'dinner', 'servings' => 1, 'is_veg' => true]);
        $this->fakeAi(['days' => [
            ['breakfast' => $upma, 'dinner' => $dal],
            ['breakfast' => 999999, 'dinner' => $secret->id],
            ['breakfast' => $upma, 'dinner' => $dal], // beyond requested days
        ]]);

        $plan = $this->actingAs($this->user)->postJson('/api/ai/meal-plan', [
            'start' => '2026-10-05', 'days' => 2, 'meals' => ['breakfast', 'dinner'], 'goal' => 'high_protein', 'servings' => 3,
        ])->assertOk()->json('data');

        $this->assertSame([
            ['date' => '2026-10-05', 'meal_type' => 'breakfast', 'recipe_id' => $upma, 'servings' => 3, 'recipe_name' => 'Upma'],
            ['date' => '2026-10-05', 'meal_type' => 'dinner', 'recipe_id' => $dal, 'servings' => 3, 'recipe_name' => 'Dal'],
        ], $plan);
        $this->assertSame(0, MealPlan::count());
        Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'high protein') && ! str_contains($r['messages'][1]['content'], 'Secret'));
    }

    public function test_bulk_meal_plan_is_atomic_and_scoped(): void
    {
        $dal = Recipe::where('name', 'Dal')->value('id');
        $secret = Recipe::create(['household_id' => User::factory()->create()->household_id, 'name' => 'Secret', 'meal_type' => 'dinner', 'servings' => 1, 'is_veg' => true]);

        $this->actingAs($this->user)->postJson('/api/meal-plans/bulk', ['entries' => [
            ['date' => '2026-10-05', 'meal_type' => 'lunch', 'recipe_id' => $dal, 'servings' => 2],
            ['date' => '2026-10-05', 'meal_type' => 'dinner', 'recipe_id' => $secret->id, 'servings' => 2],
        ]])->assertJsonValidationErrors('entries.1.recipe_id');
        $this->assertSame(0, MealPlan::count());

        $this->postJson('/api/meal-plans/bulk', ['entries' => [
            ['date' => '2026-10-05', 'meal_type' => 'lunch', 'recipe_id' => $dal, 'servings' => 2],
        ]])->assertCreated();
        $this->assertSame($this->user->household_id, MealPlan::first()->household_id);
    }

    public function test_falls_back_to_second_model_when_busy_or_unreadable(): void
    {
        config(['services.ai.model' => 'main', 'services.ai.fallback_model' => 'lite']);
        $ok = Http::response(['choices' => [['message' => ['content' => json_encode(['items' => [['name' => 'Egg', 'quantity' => 6, 'unit' => 'piece']]])]]]]);

        Http::fakeSequence('*/chat/completions')
            ->push(['error' => 'busy'], 503)->pushResponse($ok)                            // busy -> fallback
            ->push(['choices' => [['message' => ['content' => '{"items": [']]]])->pushResponse($ok); // cut off -> fallback

        $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'some eggs please'])->assertOk()->assertJsonPath('data.0.name', 'Egg');
        $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'some eggs please'])->assertOk()->assertJsonPath('data.0.name', 'Egg');

        Http::assertSent(fn ($r) => $r['model'] === 'lite');
    }

    public function test_busy_without_fallback_gives_friendly_message(): void
    {
        config(['services.ai.fallback_model' => null]);
        Http::fake(['*/chat/completions' => Http::response([], 429)]);

        $this->actingAs($this->user)->postJson('/api/ai/pantry-parse', ['text' => 'some eggs please'])
            ->assertStatus(503)->assertJsonPath('message', fn ($m) => str_contains($m, 'busy'));
    }

    public function test_ai_endpoints_require_auth(): void
    {
        $this->postJson('/api/ai/pantry-parse', ['text' => 'x'])->assertUnauthorized();
    }
}
