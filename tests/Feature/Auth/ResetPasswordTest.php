<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset-flow@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset-flow@example.com',
            'token' => $token,
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPassword!234', $user->fresh()->password));
    }

    public function test_reset_password_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'bad-token@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'bad-token@example.com',
            'token' => 'invalid-token',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_password_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'token', 'password']);
    }
}
