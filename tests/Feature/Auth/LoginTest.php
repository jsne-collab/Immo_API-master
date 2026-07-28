<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login-email@example.com',
            'password' => Hash::make('Password!234'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'login-email@example.com',
            'password' => 'Password!234',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_a_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+22891112233',
            'password' => Hash::make('Password!234'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '+22891112233',
            'password' => 'Password!234',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('Password!234'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'wrongpass@example.com',
            'password' => 'Nope!234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['login']);
    }

    public function test_login_fails_with_unknown_identifier(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'ghost@example.com',
            'password' => 'Password!234',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['login']);
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['login', 'password']);
    }
}
