<?php

namespace Tests\Feature\Message;

use App\Models\Lease;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReadAndDeleteMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_receiver_can_mark_a_message_as_read(): void
    {
        $lease = Lease::factory()->create();
        $message = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
        ]);

        $response = $this->actingAs($lease->tenant, 'sanctum')->putJson("/api/v1/messages/{$message->id}/read");

        $response->assertOk()->assertJsonPath('data.is_read', true);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'is_read' => true]);
    }

    public function test_the_sender_cannot_mark_their_own_message_as_read(): void
    {
        $lease = Lease::factory()->create();
        $message = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
        ]);

        $response = $this->actingAs($lease->owner, 'sanctum')->putJson("/api/v1/messages/{$message->id}/read");

        $response->assertStatus(403);
    }

    public function test_the_sender_can_delete_their_own_message(): void
    {
        $lease = Lease::factory()->create();
        $message = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
        ]);

        $response = $this->actingAs($lease->owner, 'sanctum')->deleteJson("/api/v1/messages/{$message->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_the_receiver_cannot_delete_a_message_they_did_not_send(): void
    {
        $lease = Lease::factory()->create();
        $message = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
        ]);

        $response = $this->actingAs($lease->tenant, 'sanctum')->deleteJson("/api/v1/messages/{$message->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }
}
