<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_with_own_household_and_token(): void
    {
        $res = $this->postJson('/api/register', [
            'name' => 'Meena',
            'email' => 'Meena@Example.com',
            'password' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'meena@example.com')
            ->assertJsonPath('user.household.name', "Meena's Kitchen");

        $this->assertNotEmpty($res->json('token'));
        $this->assertDatabaseCount('households', 1);
    }

    public function test_each_registration_gets_a_separate_household(): void
    {
        $a = $this->postJson('/api/register', ['name' => 'A', 'email' => 'a@x.com', 'password' => 'password123']);
        $b = $this->postJson('/api/register', ['name' => 'B', 'email' => 'b@x.com', 'password' => 'password123']);

        $this->assertNotEquals($a->json('user.household_id'), $b->json('user.household_id'));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'a@x.com']);

        $this->postJson('/api/register', ['name' => 'A', 'email' => 'a@x.com', 'password' => 'password123'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'a@x.com']);

        $this->postJson('/api/login', ['email' => 'a@x.com', 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'user' => ['household']]);

        $this->postJson('/api/login', ['email' => 'a@x.com', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_profile_requires_auth(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->postJson('/api/register', ['name' => 'A', 'email' => 'a@x.com', 'password' => 'password123'])
            ->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_update_profile_and_household_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/profile', ['name' => 'New', 'household_name' => 'Amma Veedu'])
            ->assertOk()
            ->assertJsonPath('user.name', 'New')
            ->assertJsonPath('user.household.name', 'Amma Veedu');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/profile', ['password' => 'newpassword'])
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($user)->putJson('/api/profile', ['password' => 'newpassword', 'current_password' => 'password'])
            ->assertOk();
    }
}
