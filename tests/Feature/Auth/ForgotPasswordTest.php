<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_reset_notification_for_an_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset-me@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset-me@example.com',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        Notification::assertSentTo($user, PasswordResetToken::class);
    }

    public function test_forgot_password_returns_a_generic_success_for_an_unknown_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        Notification::assertNothingSent();
    }

    public function test_forgot_password_fails_with_an_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_rejects_a_google_only_account(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'google-only@example.com',
            'password' => null,
            'google_id' => 'google-sub-abc',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'google-only@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
        Notification::assertNothingSent();
    }
}
