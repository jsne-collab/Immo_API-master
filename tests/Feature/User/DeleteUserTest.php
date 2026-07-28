<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_delete_their_own_account_with_the_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password!234')]);
        $user->createToken('mobile');

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/users/{$user->id}", [
            'password' => 'Password!234',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_delete_fails_with_the_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password!234')]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/users/{$user->id}", [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_a_user_cannot_delete_another_users_account(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password!234')]);
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/users/{$other->id}", [
            'password' => 'Password!234',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_delete_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$user->id}", ['password' => 'whatever']);

        $response->assertStatus(401);
    }
}
