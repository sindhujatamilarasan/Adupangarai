<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use App\Support\Coach;
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
            ->assertJsonPath('errors.email.0', 'இமெயில் இல்லன்னா பாஸ்வேர்ட் தப்பு.');

        $this->postJson('/api/register', [], ['Accept-Language' => 'ta'])
            ->assertJsonPath('errors.email.0', 'இமெயில் போடுங்க.');

        $user = User::factory()->create();
        $item = PantryItem::create(['household_id' => $user->household_id, 'ingredient_id' => Ingredient::where('name', 'Egg')->value('id'), 'quantity' => 2, 'unit' => 'piece']);
        $this->actingAs($user)->postJson("/api/pantry/{$item->id}/adjust", ['type' => 'DISCARDED', 'quantity' => 5], ['Accept-Language' => 'ta'])
            ->assertJsonPath('errors.quantity.0', 'முட்டை போதாது: கிச்சன்ல 2 piece தான் இருக்கு.');
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
        $this->postJson('/api/register', ['name' => 'Meena', 'email' => 'm@x.com', 'password' => 'password123', 'locale' => 'ta'], ['Accept-Language' => 'ta'])
            ->assertCreated()->assertJsonPath('user.locale', 'ta')->assertJsonPath('user.household.name', 'எங்க வீட்டு கிச்சன்');
    }

    public function test_every_server_message_has_tamil(): void
    {
        $ta = json_decode(file_get_contents(lang_path('ta.json')), true);
        $keys = array_column(Coach::QUICK_FOODS, 'name');
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path())) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            $str = "'((?:[^'\\\\]|\\\\.)+)'";
            preg_match_all("/__\\($str/", $code, $plain);
            preg_match_all("/__\\([^'()]+\\?\\s*$str\\s*:\\s*$str/", $code, $ternary);
            $keys = [...$keys, ...array_map('stripslashes', [...$plain[1], ...$ternary[1], ...$ternary[2]])];
        }

        $missing = array_values(array_diff(array_unique($keys), array_keys($ta)));
        $this->assertSame([], $missing, 'Missing in lang/ta.json');
    }
}
