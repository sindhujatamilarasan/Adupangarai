<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NutritionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function fakeAi(array|string $answer, int $status = 200): void
    {
        Http::fake(['*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => is_string($answer) ? $answer : json_encode($answer)]]],
        ], $status)]);
    }

    private function ownRecipe(): Recipe
    {
        $recipe = Recipe::create(['household_id' => $this->user->household_id, 'name' => 'Egg Bhurji', 'meal_type' => 'breakfast', 'servings' => 2, 'is_veg' => false]);
        $recipe->ingredients()->create(['ingredient_id' => Ingredient::where('name', 'Egg')->value('id'), 'quantity' => 4, 'unit' => 'piece']);

        return $recipe;
    }

    public function test_health_tags_come_from_thresholds(): void
    {
        $tags = fn ($values) => (new Recipe(array_combine(['calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g'], $values)))->health_tags;

        $this->assertSame(['high_protein', 'low_calorie'], $tags([300, 15, 10, 10, 2]));
        $this->assertSame(['high_fiber'], $tags([351, 14.9, 60, 10, 6]));
        $this->assertSame([], (new Recipe)->health_tags);
    }

    public function test_ai_estimate_is_stored_and_prompt_describes_the_recipe(): void
    {
        $this->fakeAi(['calories' => 180, 'protein_g' => 13.2, 'carbs_g' => 2, 'fat_g' => 12, 'fiber_g' => 0.5]);
        $recipe = $this->ownRecipe();

        $this->actingAs($this->user)->postJson("/api/recipes/{$recipe->id}/nutrition")
            ->assertOk()->assertJsonPath('data.protein_g', 13.2)->assertJsonPath('data.health_tags', ['low_calorie']);

        $this->assertEquals(180, $recipe->fresh()->calories);
        Http::assertSent(fn (Request $r) => str_contains($r['messages'][1]['content'], '4 piece Egg') && str_contains($r['messages'][1]['content'], '2 servings'));
    }

    public function test_fenced_json_answers_are_accepted(): void
    {
        $this->fakeAi("```json\n{\"calories\": 200, \"protein_g\": 10, \"carbs_g\": 5, \"fat_g\": 14, \"fiber_g\": 1}\n```");

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->ownRecipe()->id}/nutrition")->assertOk();
    }

    public function test_unrealistic_or_unreadable_answers_are_rejected(): void
    {
        $recipe = $this->ownRecipe();

        $this->fakeAi(['calories' => 99999, 'protein_g' => 10, 'carbs_g' => 5, 'fat_g' => 5, 'fiber_g' => 1]);
        $this->actingAs($this->user)->postJson("/api/recipes/{$recipe->id}/nutrition")->assertStatus(503);

        $this->fakeAi('not json');
        $this->actingAs($this->user)->postJson("/api/recipes/{$recipe->id}/nutrition")->assertStatus(503);

        $this->assertNull($recipe->fresh()->calories);
    }

    public function test_ai_down_returns_friendly_503(): void
    {
        $this->fakeAi([], 500);

        $this->actingAs($this->user)->postJson("/api/recipes/{$this->ownRecipe()->id}/nutrition")
            ->assertStatus(503)->assertJsonPath('message', fn ($m) => str_contains($m, 'AI'));
    }

    public function test_cannot_estimate_builtin_or_other_households_recipes(): void
    {
        Http::fake();
        $builtin = Recipe::whereNull('household_id')->first();
        $theirs = $this->ownRecipe();

        $this->actingAs($this->user)->postJson("/api/recipes/{$builtin->id}/nutrition")->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson("/api/recipes/{$theirs->id}/nutrition")->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_editing_a_recipe_clears_its_estimate(): void
    {
        $recipe = $this->ownRecipe();
        $recipe->update(['calories' => 200, 'protein_g' => 12, 'carbs_g' => 2, 'fat_g' => 14, 'fiber_g' => 0]);

        $this->actingAs($this->user)->putJson("/api/recipes/{$recipe->id}", [
            'name' => 'Egg Bhurji', 'meal_type' => 'breakfast', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 5, 'is_veg' => false,
            'ingredients' => [['ingredient_id' => Ingredient::where('name', 'Egg')->value('id'), 'quantity' => 6, 'unit' => 'piece']],
        ])->assertOk();

        $this->assertNull($recipe->fresh()->calories);
    }

    public function test_health_filters_on_recipes_and_cook(): void
    {
        $get = fn ($url) => $this->actingAs($this->user)->getJson($url)->assertOk();

        $highProtein = $get('/api/recipes?health=high_protein')->json('data.*.name');
        $this->assertContains('Chicken Curry', $highProtein);
        $this->assertNotContains('Masala Chai', $highProtein);
        $this->assertSame(['Chapati', 'Dal'], $get('/api/recipes?health=high_fiber')->json('data.*.name'));

        $cook = $get('/api/cook?health=low_calorie')->json('data.*.recipe');
        $this->assertTrue(collect($cook)->every(fn ($r) => $r['calories'] <= 350 && in_array('low_calorie', $r['health_tags'])));
    }

    public function test_planner_daily_totals_per_person(): void
    {
        $plan = fn ($date, $name) => MealPlan::create([
            'household_id' => $this->user->household_id, 'date' => $date, 'meal_type' => 'dinner',
            'recipe_id' => Recipe::where('name', $name)->value('id'), 'servings' => 4,
        ]);
        $plan('2026-10-05', 'Chicken Curry');  // 30 g protein / serving
        $plan('2026-10-05', 'Chapati');        // 9 g
        $plan('2026-10-06', 'Dal');            // 11 g
        Recipe::create(['household_id' => $this->user->household_id, 'name' => 'Unknown', 'meal_type' => 'dinner', 'servings' => 1, 'is_veg' => true]);
        $plan('2026-10-06', 'Unknown');

        $n = $this->actingAs($this->user)->getJson('/api/meal-plans?start=2026-10-05')->json('nutrition');

        $this->assertEquals(39, $n['2026-10-05']['protein_g']);
        $this->assertEquals(580, $n['2026-10-05']['calories']);
        $this->assertEquals(11, $n['2026-10-06']['protein_g']);
        $this->assertSame(1, $n['2026-10-06']['missing']);
    }
}
