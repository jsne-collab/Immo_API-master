<?php

namespace Tests\Feature\User;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_their_own_profile(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['city' => 'Lomé']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.profile.city', 'Lomé');
    }

    public function test_a_user_cannot_view_another_users_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/users/{$other->id}");

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_an_admin_can_view_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/users/{$other->id}");

        $response->assertOk()->assertJsonPath('data.id', $other->id);
    }

    public function test_show_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(401);
    }

    public function test_show_returns_404_for_unknown_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users/999999');

        $response->assertStatus(404)->assertJsonPath('success', false);
    }
}
