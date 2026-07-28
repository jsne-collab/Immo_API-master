<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreMaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_with_an_active_lease_can_create_a_maintenance_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Fuite d\'eau dans la salle de bain',
            'description' => 'Il y a une fuite sous le lavabo depuis hier.',
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('maintenance_requests', [
            'property_id' => $lease->property_id,
            'lease_id' => $lease->id,
            'tenant_id' => $tenant->id,
            'status' => 'new',
        ]);
    }

    public function test_a_tenant_without_an_active_lease_on_the_property_cannot_create_a_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Problème',
            'description' => 'Description du problème.',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_a_tenant_with_a_terminated_lease_cannot_create_a_request(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->terminated()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Problème',
            'description' => 'Description du problème.',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_an_owner_cannot_create_a_maintenance_request(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Problème',
            'description' => 'Description du problème.',
        ]);

        $response->assertStatus(403);
    }

    public function test_a_tenant_can_attach_a_photo_to_the_request(): void
    {
        Storage::fake('public');

        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', [
            'property_id' => $lease->property_id,
            'title' => 'Problème électrique',
            'description' => 'Une prise ne fonctionne plus.',
            'photo' => UploadedFile::fake()->create('probleme.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.photo_url'));

        $maintenanceRequest = MaintenanceRequest::first();
        Storage::disk('public')->assertExists($maintenanceRequest->photo_path);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/maintenance-requests', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id', 'title', 'description']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/maintenance-requests', []);

        $response->assertStatus(401);
    }
}
