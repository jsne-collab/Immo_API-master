<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RenewLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_renew_an_active_lease(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        $newEndDate = now()->addYear()->toDateString();

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/renew", [
            'end_date' => $newEndDate,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.end_date', $newEndDate);
    }

    public function test_renewing_an_expired_lease_reactivates_it_and_re_rents_the_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->expired()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/renew", [
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'rented']);
    }

    public function test_renew_fails_when_the_new_end_date_is_not_after_the_current_one(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/renew", [
            'end_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_a_tenant_cannot_renew_the_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/renew", [
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_renew_requires_authentication(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->postJson("/api/v1/leases/{$lease->id}/renew", [
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $response->assertStatus(401);
    }
}
