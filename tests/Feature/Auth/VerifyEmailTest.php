<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_verify_their_email_with_a_valid_otp(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'verify-me@example.com']);
        Cache::put('email_verification_otp:verify-me@example.com', '123456', now()->addMinutes(10));

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'verify-me@example.com',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'verify-me@example.com');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_fails_with_an_invalid_otp(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'wrong-otp@example.com']);
        Cache::put('email_verification_otp:wrong-otp@example.com', '123456', now()->addMinutes(10));

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'wrong-otp@example.com',
            'code' => '654321',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['code']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_fails_when_otp_has_expired_or_was_never_sent(): void
    {
        User::factory()->unverified()->create(['email' => 'no-otp@example.com']);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'no-otp@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_verify_email_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'code']);
    }
}
