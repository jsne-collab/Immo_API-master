<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowMaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_only_sees_their_own_requests(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);
        MaintenanceRequest::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/maintenance-requests');

        $response->assertOk()->assertJsonCount(1, 'data.items');
    }

    public function test_an_owner_sees_requests_for_their_properties(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);
        MaintenanceRequest::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/maintenance-requests');

        $response->assertOk()->assertJsonCount(1, 'data.items');
    }

    public function test_the_tenant_who_created_the_request_can_view_it(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertOk()->assertJsonPath('data.id', $maintenanceRequest->id);
    }

    public function test_the_property_owner_can_view_the_request(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertOk()->assertJsonPath('data.id', $maintenanceRequest->id);
    }

    public function test_an_unrelated_user_cannot_view_the_request(): void
    {
        $stranger = User::factory()->tenant()->create();
        $maintenanceRequest = MaintenanceRequest::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}");

        $response->assertStatus(403);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/maintenance-requests');

        $response->assertStatus(401);
    }
}
