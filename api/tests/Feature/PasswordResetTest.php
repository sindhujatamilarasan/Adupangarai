<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_and_reset_password_flow(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://adupangarai.in']);
        $user = User::factory()->create(['email' => 'me@example.com', 'locale' => 'ta']);
        $oldToken = $user->createToken('app')->plainTextToken;

        $known = $this->postJson('/api/forgot-password', ['email' => 'ME@example.com'])->assertOk()->json('message');
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');
        $this->assertSame($known, $unknown); // no way to tell which emails have accounts

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user, &$token) {
            $token = $n->token;
            $url = $n->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://adupangarai.in/reset-password?token=', $url);
            $this->assertStringContainsString('email=me%40example.com', $url);

            return true;
        });
        $this->assertSame('ta', $user->preferredLocale()); // the email goes out in Tamil

        $this->postJson('/api/reset-password', ['token' => 'wrong', 'email' => 'me@example.com', 'password' => 'new-password-1'])->assertStatus(422);
        $this->postJson('/api/reset-password', ['token' => $token, 'email' => 'me@example.com', 'password' => 'new-password-1'])->assertOk();

        $this->postJson('/api/login', ['email' => 'me@example.com', 'password' => 'new-password-1'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($oldToken)->getJson('/api/profile')->assertUnauthorized(); // other devices signed out
        // A link works only once.
        $this->postJson('/api/reset-password', ['token' => $token, 'email' => 'me@example.com', 'password' => 'another-pass'])->assertStatus(422);
    }
}
