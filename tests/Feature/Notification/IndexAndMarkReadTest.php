<?php

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndMarkReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }

    public function test_the_owner_can_mark_their_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()->assertJsonPath('data.is_read', true);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $stranger = User::factory()->create();
        $notification = Notification::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }

    public function test_mark_all_read_flips_only_the_users_own_unread_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        $otherUsersNotification = Notification::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/notifications/read-all');

        $response->assertOk()->assertJsonPath('data.updated', 3);
        $this->assertSame(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
        $this->assertFalse($otherUsersNotification->fresh()->is_read);
    }

    public function test_the_owner_can_delete_their_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_a_user_cannot_delete_another_users_notification(): void
    {
        $stranger = User::factory()->create();
        $notification = Notification::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }
}
