<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_update_their_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/leases/{$lease->id}", [
            'monthly_rent' => 123456,
        ]);

        $response->assertOk()->assertJsonPath('data.monthly_rent', 123456);
        $this->assertDatabaseHas('leases', ['id' => $lease->id, 'monthly_rent' => 123456]);
    }

    public function test_the_tenant_cannot_update_the_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->putJson("/api/v1/leases/{$lease->id}", [
            'monthly_rent' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_update_requires_authentication(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->putJson("/api/v1/leases/{$lease->id}", ['monthly_rent' => 1]);

        $response->assertStatus(401);
    }
}
