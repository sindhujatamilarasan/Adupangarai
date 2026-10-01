<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Foods;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** AI cost control: known foods never reach the AI, AI can be switched off, and each person has a daily cap. */
class AiCostTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAi(): void
    {
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['items' => [['name' => 'Kozhukattai (2)', 'calories' => 180, 'protein_g' => 3]]])]]]])]);
    }

    public function test_common_foods_are_read_without_ai(): void
    {
        Http::fake(); // any AI call would be recorded
        $user = User::factory()->create();

        $r = $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => 'I had 2 vadas and a filter coffee'])->assertOk();
        $this->assertSame('rules', $r->json('source'));
        $this->assertSame([['name' => 'Vada (1) × 2', 'calories' => 280, 'protein_g' => 8], ['name' => 'Filter coffee', 'calories' => 80, 'protein_g' => 2]], $r->json('data'));

        $ta = $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => 'ரெண்டு இட்லி, சாம்பார்'], ['Accept-Language' => 'ta'])->json();
        $this->assertSame('rules', $ta['source']);
        $this->assertSame(['இட்லி (1) × 2', 'சாம்பார் (1 கப்)'], array_column($ta['data'], 'name'));
        $this->assertEquals(210, array_sum(array_column($ta['data'], 'calories')));

        app()->setLocale('en');
        $this->assertSame(['Bajji (2)'], array_column(Foods::parse('2 bajji'), 'name')); // entry already is 2 pieces
        $this->assertSame('Masala dosa (1)', Foods::parse('masala dosa')[0]['name']); // longest match wins
        $this->assertNull(Foods::parse('2 vada and a dragon fruit')); // unknown part -> needs AI
        $this->assertNull(Foods::parse('medu vada at the stall near the temple')); // too vague for the list
        $this->assertSame('Dosa (1)', Foods::parse('one hot dosa')[0]['name']);
        $this->assertNotEmpty($this->actingAs($user)->getJson('/api/coach/foods?search=dosa')->json('data'));
        Http::assertNothingSent();
    }

    public function test_unknown_food_uses_ai_within_the_daily_limit(): void
    {
        $this->fakeAi();
        config(['services.ai.daily_limit' => 2]);
        $user = User::factory()->create();
        $ask = fn () => $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => '2 kozhukattai']);

        $ask()->assertOk()->assertJsonPath('source', 'ai');
        $ask()->assertOk();
        $ask()->assertStatus(503)->assertJsonPath('message', 'You’ve used today’s 2 AI helps. Type it in, or try again tomorrow.');
        // Known foods still work after the limit.
        $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => 'idli'])->assertOk();
        // Another person has their own allowance.
        $this->actingAs(User::factory()->create())->postJson('/api/coach/food/estimate', ['text' => '2 kozhukattai'])->assertOk();
        // A new day resets it.
        $this->travel(1)->days();
        $ask()->assertOk();
    }

    public function test_ai_can_be_switched_off(): void
    {
        $this->fakeAi();
        config(['services.ai.enabled' => false]);
        $this->seed(IngredientSeeder::class);
        $user = User::factory()->create();

        $this->getJson('/api/auth/config')->assertJsonPath('ai_enabled', false);
        $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => '2 kozhukattai'])->assertStatus(503);
        $this->actingAs($user)->postJson('/api/coach/food/estimate', ['text' => '2 idli'])->assertOk(); // no AI needed
        $this->actingAs($user)->postJson('/api/ai/pantry-parse', ['text' => '1 kg chicken'])->assertOk(); // simple lists need no AI
        Http::assertNothingSent();
    }
}
