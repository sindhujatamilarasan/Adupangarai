<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use App\Support\MealPlanner;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
        $this->travelTo('2026-10-05 09:00');
    }

    private function suggest(array $overrides = []): array
    {
        return $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', [
            'start' => '2026-10-05', 'days' => 7, 'meals' => ['breakfast', 'lunch', 'dinner'], 'goal' => 'balanced', 'servings' => 2, ...$overrides,
        ])->assertOk()->json();
    }

    private function names(array $plan): array
    {
        return array_column($plan['data'], 'recipe_name');
    }

    public function test_expiring_basmati_does_not_put_two_fried_rices_on_one_day(): void
    {
        PantryItem::create([
            'household_id' => $this->user->household_id, 'ingredient_id' => Ingredient::where('name', 'Basmati Rice')->value('id'),
            'quantity' => 1000, 'unit' => 'g', 'expiry_date' => '2026-10-06',
        ]);

        $plan = $this->suggest(['meals' => ['lunch', 'dinner']]);

        foreach (collect($plan['data'])->groupBy('date') as $date => $day) {
            $fried = $day->filter(fn ($p) => str_contains($p['recipe_name'], 'Fried Rice'));
            $this->assertLessThanOrEqual(1, $fried->count(), "Two fried rices on $date");
        }
    }

    public function test_no_near_duplicate_dishes_share_a_day(): void
    {
        $recipes = Recipe::with('ingredients.ingredient')->get()->keyBy('id');
        $plan = $this->suggest();

        foreach (collect($plan['data'])->groupBy('date') as $day) {
            $ids = $day->pluck('recipe_id')->all();
            foreach ($ids as $a) {
                foreach ($ids as $b) {
                    if ($a < $b) {
                        $this->assertLessThan(MealPlanner::SIMILARITY_LIMIT, MealPlanner::similarity(
                            MealPlanner::mainIngredients($recipes[$a]), MealPlanner::mainIngredients($recipes[$b]),
                        ));
                    }
                }
            }
        }
    }

    public function test_same_meat_or_paneer_not_twice_a_day_and_drinks_are_not_breakfast(): void
    {
        $recipes = Recipe::with('ingredients.ingredient.category')->get()->keyBy('id');

        foreach (['high_protein', 'low_calorie', 'balanced'] as $goal) {
            $plan = $this->suggest(['goal' => $goal]);
            foreach (collect($plan['data'])->groupBy('date') as $date => $day) {
                $heroes = $day->flatMap(fn ($p) => MealPlanner::heroIngredients($recipes[$p['recipe_id']]));
                $this->assertSame($heroes->count(), $heroes->unique()->count(), "$goal: same meat/paneer twice on $date");
            }
            $this->assertNotContains('Masala Chai', collect($plan['data'])->where('meal_type', 'breakfast')->pluck('recipe_name')->all());
        }
    }

    public function test_no_repeats_within_three_days_and_at_most_twice_a_week(): void
    {
        $plan = $this->suggest(['days' => 3]);
        $this->assertSame(count($plan['data']), count(array_unique(array_column($plan['data'], 'recipe_id'))));
        $this->assertCount(9, $plan['data']);

        foreach (['balanced', 'high_protein', 'low_calorie'] as $goal) {
            $week = collect($this->suggest(['goal' => $goal])['data'])->groupBy('recipe_id');
            foreach ($week as $uses) {
                $this->assertLessThanOrEqual(MealPlanner::MAX_USES, $uses->count());
                $dates = $uses->pluck('date')->sort()->values();
                for ($i = 1; $i < $dates->count(); $i++) {
                    $this->assertGreaterThanOrEqual(MealPlanner::REPEAT_GAP_DAYS, now()->parse($dates[$i - 1])->diffInDays($dates[$i]));
                }
            }
        }
    }

    public function test_goals_really_change_the_plan(): void
    {
        $protein = $this->suggest(['goal' => 'high_protein'])['summary'];
        $light = $this->suggest(['goal' => 'low_calorie'])['summary'];
        $balanced = $this->suggest(['goal' => 'balanced'])['summary'];

        $this->assertGreaterThan($light['avg_protein_g'], $protein['avg_protein_g']);
        $this->assertGreaterThan($balanced['avg_protein_g'], $protein['avg_protein_g']);
        $this->assertLessThan($protein['avg_calories'], $light['avg_calories']);
        $this->assertLessThanOrEqual($balanced['avg_calories'], $light['avg_calories']);
    }

    public function test_meal_times_fit_and_seed_gives_variety(): void
    {
        $plan = $this->suggest(['days' => 2, 'meals' => ['breakfast']]);
        foreach ($plan['data'] as $p) {
            $this->assertContains(Recipe::find($p['recipe_id'])->meal_type, ['breakfast', 'snack']);
        }

        $a = $this->names($this->suggest(['seed' => 1]));
        $this->assertSame($a, $this->names($this->suggest(['seed' => 1])), 'same seed, same plan');
        $this->assertNotSame($a, $this->names($this->suggest(['seed' => 7])), 'shuffle gives a different plan');
    }

    public function test_veg_plan_is_fully_vegetarian_and_nonveg_leans_to_meat_fish_egg(): void
    {
        $isVeg = Recipe::pluck('is_veg', 'id');

        foreach (['balanced', 'high_protein', 'low_calorie'] as $goal) {
            $veg = $this->suggest(['goal' => $goal, 'diet' => 'veg'])['data'];
            $this->assertNotEmpty($veg);
            $this->assertTrue(collect($veg)->every(fn ($p) => $isVeg[$p['recipe_id']]), "$goal veg plan has non-veg");
        }

        $count = fn ($diet) => collect($this->suggest(['diet' => $diet])['data'])->reject(fn ($p) => $isVeg[$p['recipe_id']])->count();
        $this->assertGreaterThan($count('any'), $count('nonveg'));

        $this->actingAs($this->user)->postJson('/api/meal-plans/suggest', [
            'start' => '2026-10-05', 'days' => 1, 'meals' => ['lunch'], 'goal' => 'balanced', 'servings' => 2, 'diet' => 'vegan',
        ])->assertJsonValidationErrors('diet');
    }

    public function test_suggesting_saves_nothing_and_ignores_other_households_recipes(): void
    {
        Recipe::create(['household_id' => User::factory()->create()->household_id, 'name' => 'Secret', 'meal_type' => 'dinner', 'servings' => 1, 'is_veg' => true, 'calories' => 100, 'protein_g' => 90, 'carbs_g' => 1, 'fat_g' => 1, 'fiber_g' => 1]);

        $plan = $this->suggest(['goal' => 'high_protein']);

        $this->assertNotContains('Secret', $this->names($plan));
        $this->assertSame(0, MealPlan::count());
    }
}
