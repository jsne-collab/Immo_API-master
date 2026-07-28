<?php

namespace Tests\Feature\Notification;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validating_a_payment_notifies_the_tenant(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-08',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $lease->tenant_id,
            'type' => Notification::TYPE_PAYMENT_VALIDATED,
        ]);
    }

    public function test_sending_a_message_notifies_the_receiver(): void
    {
        $lease = Lease::factory()->create();

        $this->actingAs($lease->owner, 'sanctum')->postJson('/api/v1/messages', [
            'receiver_id' => $lease->tenant_id,
            'content' => 'Bonjour',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $lease->tenant_id,
            'type' => Notification::TYPE_NEW_MESSAGE,
        ]);
    }

    public function test_creating_a_maintenance_request_notifies_the_owner(): void
    {
        $lease = Lease::factory()->create();

        $this->actingAs($lease->tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Fuite',
            'description' => 'Fuite sous l\'évier.',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $lease->owner_id,
            'type' => Notification::TYPE_MAINTENANCE_REQUEST_CREATED,
        ]);
    }

    public function test_commenting_on_a_maintenance_request_notifies_the_other_party(): void
    {
        $lease = Lease::factory()->create();
        $maintenanceRequest = MaintenanceRequest::factory()->create([
            'lease_id' => $lease->id,
            'property_id' => $lease->property_id,
            'tenant_id' => $lease->tenant_id,
        ]);

        $this->actingAs($lease->owner, 'sanctum')->postJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/comments", [
            'comment' => 'Un technicien passera demain.',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $lease->tenant_id,
            'type' => Notification::TYPE_MAINTENANCE_COMMENT,
        ]);
    }
}
