<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_the_account_removes_all_its_data_and_nothing_else(): void
    {
        Storage::fake('public');
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $builtIn = Recipe::count();
        $user = User::factory()->create(['email' => 'me@example.com']);
        $other = User::factory()->create();
        $h = $user->household_id;

        $mine = Recipe::create(['household_id' => $h, 'name' => 'My dosa', 'meal_type' => 'breakfast', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 5, 'is_veg' => true,
            'image_path' => UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg')->store('recipes', 'public')]);
        PantryItem::create(['household_id' => $h, 'ingredient_id' => Ingredient::first()->id, 'quantity' => 1, 'unit' => Ingredient::first()->default_unit]);
        MealPlan::create(['household_id' => $h, 'date' => '2026-10-05', 'meal_type' => 'lunch', 'recipe_id' => $mine->id, 'servings' => 1]);
        FoodLog::create(['user_id' => $user->id, 'date' => '2026-10-05', 'name' => 'Tea', 'calories' => 60, 'source' => 'quick']);
        $token = $user->createToken('app')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/profile', ['confirm' => 'wrong@example.com'])->assertStatus(422);
        $this->assertModelExists($user);

        $this->withToken($token)->deleteJson('/api/profile', ['confirm' => ' ME@example.com '])->assertOk();

        $this->assertModelMissing($user);
        $this->assertNull(Household::find($h));
        $this->assertSame(0, PantryItem::where('household_id', $h)->count() + MealPlan::where('household_id', $h)->count() + FoodLog::count());
        Storage::disk('public')->assertMissing($mine->image_path);
        $this->assertSame($builtIn, Recipe::count()); // built-in recipes stay
        $this->assertModelExists($other);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_privacy_and_deletion_pages_are_public(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy policy')->assertSee('Delete account')->assertSee(config('app.contact_email'));
        $this->get('/delete-account')->assertOk()->assertSee('What gets deleted');
    }
}
