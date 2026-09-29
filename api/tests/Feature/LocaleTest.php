<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([IngredientSeeder::class, RecipeSeeder::class]);
    }

    public function test_labels_follow_the_requested_language_but_names_stay_canonical(): void
    {
        $user = User::factory()->create();

        $egg = collect($this->actingAs($user)->getJson('/api/ingredients?search=egg', ['Accept-Language' => 'ta'])->json('data'))->firstWhere('name', 'Egg');
        $this->assertSame('முட்டை', $egg['label']);
        $this->assertSame('பால் & முட்டை', $egg['category']['label']);

        $recipe = collect($this->getJson('/api/recipes', ['Accept-Language' => 'ta'])->json('data'))->firstWhere('name', 'Curd Rice');
        $this->assertSame('தயிர் சாதம்', $recipe['label']);

        $recipe = collect($this->getJson('/api/recipes', ['Accept-Language' => 'en'])->json('data'))->firstWhere('name', 'Curd Rice');
        $this->assertSame('Curd Rice', $recipe['label']);
    }

    public function test_messages_and_validation_are_translated(): void
    {
        $this->postJson('/api/login', ['email' => 'x@y.com', 'password' => 'nope'], ['Accept-Language' => 'ta'])
            ->assertJsonPath('errors.email.0', 'மின்னஞ்சல் அல்லது கடவுச்சொல் தவறு.');

        $this->postJson('/api/register', [], ['Accept-Language' => 'ta'])
            ->assertJsonPath('errors.email.0', 'மின்னஞ்சல் தேவை.');

        $user = User::factory()->create();
        $item = PantryItem::create(['household_id' => $user->household_id, 'ingredient_id' => Ingredient::where('name', 'Egg')->value('id'), 'quantity' => 2, 'unit' => 'piece']);
        $this->actingAs($user)->postJson("/api/pantry/{$item->id}/adjust", ['type' => 'DISCARDED', 'quantity' => 5], ['Accept-Language' => 'ta'])
            ->assertJsonPath('errors.quantity.0', 'போதுமான முட்டை இல்லை: சமையலறையில் 2 piece மட்டுமே உள்ளது.');
    }

    public function test_saved_language_is_used_when_the_app_does_not_say(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/profile', ['locale' => 'ta'])->assertOk()->assertJsonPath('user.locale', 'ta');

        $recipe = collect($this->actingAs($user->fresh())->getJson('/api/recipes', ['Accept-Language' => ''])->json('data'))->firstWhere('name', 'Dal');
        $this->assertSame('பருப்பு', $recipe['label']);

        $this->putJson('/api/profile', ['locale' => 'fr'])->assertJsonValidationErrors('locale');
    }

    public function test_search_works_with_tamil_names(): void
    {
        $user = User::factory()->create();

        $this->assertSame(['Curd Rice'], $this->actingAs($user)->getJson('/api/recipes?search='.urlencode('தயிர்'))->json('data.*.name'));
        $this->assertContains('Onion', $this->getJson('/api/ingredients?search='.urlencode('வெங்காய'))->json('data.*.name'));
    }

    public function test_register_saves_the_chosen_language(): void
    {
        $this->postJson('/api/register', ['name' => 'Meena', 'email' => 'm@x.com', 'password' => 'password123', 'locale' => 'ta'])
            ->assertCreated()->assertJsonPath('user.locale', 'ta');
    }
}
