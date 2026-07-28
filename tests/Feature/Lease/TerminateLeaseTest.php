<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TerminateLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_terminate_an_active_lease(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", []);

        $response->assertOk()->assertJsonPath('data.status', 'terminated');
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'available']);
    }

    public function test_terminating_a_lease_on_a_unit_frees_only_that_unit(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = PropertyUnit::factory()->rented()->for($property)->create();
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", [])->assertOk();

        $this->assertDatabaseHas('property_units', ['id' => $unit->id, 'status' => 'available']);
    }

    public function test_terminate_with_an_earlier_date_shortens_the_lease(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $terminationDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", [
            'termination_date' => $terminationDate,
        ]);

        $response->assertOk()->assertJsonPath('data.end_date', $terminationDate);
    }

    public function test_a_tenant_cannot_terminate_the_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", []);

        $response->assertStatus(403);
    }

    public function test_cannot_terminate_an_already_terminated_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->terminated()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", []);

        $response->assertStatus(422);
    }

    public function test_terminate_requires_authentication(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->postJson("/api/v1/leases/{$lease->id}/terminate", []);

        $response->assertStatus(401);
    }
}
