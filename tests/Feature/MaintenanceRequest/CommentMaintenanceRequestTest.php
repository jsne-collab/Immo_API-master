<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentMaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tenant_can_post_a_comment_on_their_own_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/comments", [
            'comment' => 'Le problème persiste ce matin.',
        ]);

        $response->assertCreated()->assertJsonCount(1, 'data.comments');
        $this->assertDatabaseHas('maintenance_comments', [
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => $tenant->id,
            'comment' => 'Le problème persiste ce matin.',
        ]);
    }

    public function test_the_property_owner_can_post_a_comment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/comments", [
            'comment' => 'Un technicien passera demain.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('maintenance_comments', [
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_an_unrelated_user_cannot_comment(): void
    {
        $stranger = User::factory()->tenant()->create();
        $maintenanceRequest = MaintenanceRequest::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->postJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/comments", [
            'comment' => 'Je regarde ça.',
        ]);

        $response->assertStatus(403);
    }

    public function test_comment_requires_a_body(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $maintenanceRequest = MaintenanceRequest::factory()->create(['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson("/api/v1/maintenance-requests/{$maintenanceRequest->id}/comments", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['comment']);
    }
}
