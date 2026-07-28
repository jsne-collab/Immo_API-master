<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'name' => 'New Name',
            'address' => '12 Rue du Port',
            'city' => 'Lomé',
            'date_of_birth' => '1995-05-20',
            'id_card_number' => 'ID-12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.profile.city', 'Lomé')
            ->assertJsonPath('data.profile.id_card_number', 'ID-12345');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'city' => 'Lomé']);
    }

    public function test_a_user_cannot_update_another_users_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$other->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['id' => $other->id, 'name' => 'Hacked']);
    }

    public function test_an_admin_can_update_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/users/{$other->id}", [
            'name' => 'Updated By Admin',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated By Admin');
    }

    public function test_update_fails_with_a_duplicate_email(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_update_fails_with_a_duplicate_phone(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['phone' => '+22899999999']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'phone' => '+22899999999',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_update_allows_keeping_the_same_email(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'email' => 'me@example.com',
        ]);

        $response->assertOk();
    }

    public function test_update_rejects_a_date_of_birth_in_the_future(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'date_of_birth' => now()->addYear()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_update_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/v1/users/{$user->id}", ['name' => 'X']);

        $response->assertStatus(401);
    }
}
