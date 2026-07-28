<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusMaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_property_owner_can_change_the_status(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->assertDatabaseHas('maintenance_requests', ['id' => $maintenanceRequest->id, 'status' => 'in_progress']);
    }

    public function test_the_tenant_cannot_change_the_status(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/status", [
            'status' => 'resolved',
        ]);

        $response->assertStatus(403);
    }

    public function test_an_unrelated_owner_cannot_change_the_status(): void
    {
        $owner = User::factory()->owner()->create();
        $maintenanceRequest = MaintenanceRequest::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/status", [
            'status' => 'resolved',
        ]);

        $response->assertStatus(403);
    }

    public function test_status_must_be_a_valid_value(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/status", [
            'status' => 'not-a-status',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }
}
