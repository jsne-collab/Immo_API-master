<?php

namespace Tests\Feature\Lease;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreLeaseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 100000,
            'deposit_amount' => 100000,
        ], $overrides);
    }

    public function test_an_owner_can_create_a_lease_for_their_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'guarantor_name' => 'Awa Koffi',
            'guarantor_phone' => '+22890000000',
            'occupation' => 'Enseignant',
            'monthly_income' => 150000,
        ]));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.property.id', $property->id)
            ->assertJsonPath('data.tenant.id', $tenant->id);

        $this->assertNotNull($response->json('data.contract_pdf_url'));
        $this->assertDatabaseHas('leases', ['property_id' => $property->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'rented']);
        $this->assertDatabaseHas('tenants', ['user_id' => $tenant->id, 'guarantor_name' => 'Awa Koffi']);
        $this->assertDatabaseHas('lease_documents', ['document_type' => 'contract']);
    }

    public function test_creating_a_lease_for_a_unit_marks_only_the_unit_as_rented(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = PropertyUnit::factory()->for($property)->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('property_units', ['id' => $unit->id, 'status' => 'rented']);
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'available']);
    }

    public function test_cannot_create_a_second_active_lease_for_the_same_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $tenant1 = User::factory()->tenant()->create();
        $tenant2 = User::factory()->tenant()->create();

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant1->id,
        ]))->assertCreated();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant2->id,
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_a_tenant_cannot_create_a_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_an_owner_cannot_create_a_lease_for_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_cannot_create_a_lease_with_a_non_tenant_user(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $otherOwner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $otherOwner->id,
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', []);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'property_id', 'tenant_id', 'start_date', 'end_date', 'monthly_rent', 'deposit_amount',
        ]);
    }

    public function test_store_fails_when_end_date_is_before_start_date(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', $this->payload([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/leases', $this->payload(['property_id' => 1, 'tenant_id' => 1]));

        $response->assertStatus(401);
    }
}
