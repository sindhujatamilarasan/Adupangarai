<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipePhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
        $this->user = User::factory()->create();
        $this->recipe = Recipe::create(['household_id' => $this->user->household_id, 'name' => 'Mine', 'meal_type' => 'lunch', 'servings' => 2, 'is_veg' => true]);
    }

    public function test_upload_replace_and_remove_photo(): void
    {
        $url = $this->actingAs($this->user)
            ->post("/api/recipes/{$this->recipe->id}/photo", ['photo' => UploadedFile::fake()->image('a.jpg', 800, 600)], ['Accept' => 'application/json'])
            ->assertOk()->json('data.image_url');
        $first = $this->recipe->fresh()->image_path;
        $this->assertSame('/storage/'.$first, $url);
        Storage::disk('public')->assertExists($first);

        $this->post("/api/recipes/{$this->recipe->id}/photo", ['photo' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($first);

        $second = $this->recipe->fresh()->image_path;
        $this->deleteJson("/api/recipes/{$this->recipe->id}/photo")->assertOk();
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($this->recipe->fresh()->image_url);
    }

    public function test_rejects_non_images_and_large_files(): void
    {
        $this->actingAs($this->user)
            ->post("/api/recipes/{$this->recipe->id}/photo", ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('photo');
        $this->post("/api/recipes/{$this->recipe->id}/photo", ['photo' => UploadedFile::fake()->image('big.jpg')->size(6000)], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('photo');
    }

    public function test_cannot_change_builtin_or_other_households_photos(): void
    {
        $builtin = Recipe::whereNull('household_id')->first();
        $photo = fn () => ['photo' => UploadedFile::fake()->image('a.jpg')];

        $this->actingAs($this->user)->post("/api/recipes/{$builtin->id}/photo", $photo(), ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs(User::factory()->create())->post("/api/recipes/{$this->recipe->id}/photo", $photo(), ['Accept' => 'application/json'])->assertNotFound();
        $this->assertNull($this->recipe->fresh()->image_path);
    }

    public function test_deleting_recipe_deletes_photo_and_icons_are_exposed(): void
    {
        $this->actingAs($this->user)->post("/api/recipes/{$this->recipe->id}/photo", ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $path = $this->recipe->fresh()->image_path;

        $this->deleteJson("/api/recipes/{$this->recipe->id}")->assertOk();
        Storage::disk('public')->assertMissing($path);

        $egg = collect($this->getJson('/api/ingredients?search=egg')->json('data'))->firstWhere('name', 'Egg');
        $this->assertSame('🥚', $egg['display_icon']);
    }
}
