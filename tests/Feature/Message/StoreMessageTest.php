<?php

namespace Tests\Feature\Message;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_message_their_tenant(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->actingAs($lease->owner, 'sanctum')->postJson('/api/v1/messages', [
            'receiver_id' => $lease->tenant_id,
            'content' => 'Bonjour, le loyer de ce mois est-il prévu bientôt ?',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sender.id', $lease->owner_id)
            ->assertJsonPath('data.receiver.id', $lease->tenant_id)
            ->assertJsonPath('data.is_read', false);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $lease->owner_id,
            'receiver_id' => $lease->tenant_id,
            'lease_id' => $lease->id,
        ]);
    }

    public function test_a_tenant_can_message_their_owner(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->actingAs($lease->tenant, 'sanctum')->postJson('/api/v1/messages', [
            'receiver_id' => $lease->owner_id,
            'content' => 'Bonjour, je viens de payer le loyer.',
        ]);

        $response->assertCreated()->assertJsonPath('data.receiver.id', $lease->owner_id);
    }

    public function test_a_user_cannot_message_someone_without_a_shared_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $stranger = User::factory()->tenant()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/messages', [
            'receiver_id' => $stranger->id,
            'content' => 'Bonjour',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/messages', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['receiver_id', 'content']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/messages', []);

        $response->assertStatus(401);
    }
}
