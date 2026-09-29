<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'our-client.apps.googleusercontent.com']);
    }

    private function googleSays(array $claims, int $status = 200): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'our-client.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com',
            'sub' => 'google-123', 'email' => 'Meena@Gmail.com', 'email_verified' => 'true', 'name' => 'Meena',
            'exp' => (string) (time() + 3600), ...$claims,
        ], $status)]);
    }

    public function test_config_exposes_client_id_only_when_set(): void
    {
        $this->getJson('/api/auth/config')->assertJsonPath('google_client_id', 'our-client.apps.googleusercontent.com');
        config(['services.google.client_id' => null]);
        $this->getJson('/api/auth/config')->assertJsonPath('google_client_id', null);
        $this->postJson('/api/auth/google', ['credential' => 'x'])->assertNotFound();
    }

    public function test_new_google_user_gets_an_account_household_and_token(): void
    {
        $this->googleSays([]);

        $res = $this->postJson('/api/auth/google', ['credential' => 'id-token'])->assertCreated();

        $this->assertNotEmpty($res->json('token'));
        $this->assertSame('meena@gmail.com', $res->json('user.email'));
        $this->assertSame("Meena's Kitchen", $res->json('user.household.name'));
        $this->assertSame('google-123', User::first()->google_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'id_token=id-token'));
    }

    public function test_existing_email_is_linked_not_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'meena@gmail.com']);
        $this->googleSays([]);

        $this->postJson('/api/auth/google', ['credential' => 'id-token'])->assertOk()->assertJsonPath('user.id', $existing->id);

        $this->assertSame(1, User::count());
        $this->assertSame('google-123', $existing->fresh()->google_id);
    }

    public function test_rejects_tokens_for_other_apps_unverified_or_expired(): void
    {
        foreach ([['aud' => 'someone-else'], ['email_verified' => 'false'], ['iss' => 'evil.com'], ['exp' => (string) (time() - 10)]] as $bad) {
            $this->googleSays($bad);
            $this->postJson('/api/auth/google', ['credential' => 'id-token'])->assertJsonValidationErrors('google');
        }
        $this->googleSays([], 400);
        $this->postJson('/api/auth/google', ['credential' => 'garbage'])->assertJsonValidationErrors('google');

        $this->assertSame(0, User::count());
    }
}
