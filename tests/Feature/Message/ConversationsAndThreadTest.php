<?php

namespace Tests\Feature\Message;

use App\Models\Lease;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationsAndThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversations_lists_one_entry_per_interlocutor_with_last_message_and_unread_count(): void
    {
        $lease = Lease::factory()->create();
        Message::factory()->read()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
            'content' => 'Premier message',
        ]);
        $last = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
            'content' => 'Deuxième message',
        ]);

        $response = $this->actingAs($lease->tenant, 'sanctum')->getJson('/api/v1/conversations');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertSame($lease->owner_id, $items[0]['user']['id']);
        $this->assertSame($last->id, $items[0]['last_message']['id']);
        $this->assertSame(1, $items[0]['unread_count']);
    }

    public function test_thread_returns_messages_between_the_two_users_in_chronological_order(): void
    {
        $lease = Lease::factory()->create();
        $first = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
            'content' => 'Bonjour',
        ]);
        $second = Message::factory()->fromTenant()->create([
            'lease_id' => $lease->id,
            'content' => 'Bonjour, oui ?',
        ]);
        Message::factory()->create();

        $response = $this->actingAs($lease->tenant, 'sanctum')->getJson("/api/v1/conversations/{$lease->owner_id}/messages");

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertCount(2, $items);
        $this->assertSame($first->id, $items[0]['id']);
        $this->assertSame($second->id, $items[1]['id']);
    }

    public function test_opening_the_thread_marks_received_messages_as_read(): void
    {
        $lease = Lease::factory()->create();
        $message = Message::factory()->create([
            'lease_id' => $lease->id,
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
        ]);

        $this->actingAs($lease->tenant, 'sanctum')->getJson("/api/v1/conversations/{$lease->owner_id}/messages")->assertOk();

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_conversations_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/conversations');

        $response->assertStatus(401);
    }
}
