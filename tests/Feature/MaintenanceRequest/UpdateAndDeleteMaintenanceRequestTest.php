<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAndDeleteMaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_author_can_update_a_new_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}", [
            'title' => 'Titre mis à jour',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Titre mis à jour');
    }

    public function test_the_author_cannot_update_a_request_that_is_no_longer_new(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->inProgress()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}", [
            'title' => 'Titre mis à jour',
        ]);

        $response->assertStatus(422);
    }

    public function test_the_property_owner_cannot_edit_the_request_content(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}", [
            'title' => 'Titre mis à jour',
        ]);

        $response->assertStatus(403);
    }

    public function test_the_author_can_delete_a_new_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->deleteJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('maintenance_requests', ['id' => $maintenanceRequest->id]);
    }

    public function test_the_author_cannot_delete_a_request_that_is_no_longer_new(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->resolved()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->deleteJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('maintenance_requests', ['id' => $maintenanceRequest->id]);
    }

    public function test_the_property_owner_cannot_delete_the_request(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertStatus(403);
    }
}
