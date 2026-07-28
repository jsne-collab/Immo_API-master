<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword!234')]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}/password", [
            'current_password' => 'OldPassword!234',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(Hash::check('NewPassword!234', $user->fresh()->password));
    }

    public function test_update_password_revokes_other_sessions(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword!234')]);
        $otherToken = $user->createToken('other-device')->plainTextToken;

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}/password", [
            'current_password' => 'OldPassword!234',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])->assertOk();

        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_update_password_fails_with_the_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword!234')]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}/password", [
            'current_password' => 'WrongPassword!234',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_update_password_fails_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword!234')]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}/password", [
            'current_password' => 'OldPassword!234',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'Different!234',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_a_user_cannot_update_another_users_password(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['password' => Hash::make('Password!234')]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$other->id}/password", [
            'current_password' => 'Password!234',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ]);

        $response->assertStatus(403);
    }
}
