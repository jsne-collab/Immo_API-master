<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_sees_leases_for_their_properties(): void
    {
        $owner = User::factory()->owner()->create();
        Lease::factory()->count(2)->create(['owner_id' => $owner->id]);
        Lease::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/leases');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_a_tenant_sees_only_their_own_leases(): void
    {
        $tenant = User::factory()->tenant()->create();
        Lease::factory()->create(['tenant_id' => $tenant->id]);
        Lease::factory()->count(2)->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/leases');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/leases');

        $response->assertStatus(401);
    }

    public function test_the_owner_can_view_the_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/leases/{$lease->id}");

        $response->assertOk()->assertJsonPath('data.id', $lease->id);
    }

    public function test_the_tenant_can_view_the_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/leases/{$lease->id}");

        $response->assertOk()->assertJsonPath('data.id', $lease->id);
    }

    public function test_an_unrelated_user_cannot_view_the_lease(): void
    {
        $stranger = User::factory()->create();
        $lease = Lease::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/leases/{$lease->id}");

        $response->assertStatus(403);
    }

    public function test_show_requires_authentication(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->getJson("/api/v1/leases/{$lease->id}");

        $response->assertStatus(401);
    }
}
