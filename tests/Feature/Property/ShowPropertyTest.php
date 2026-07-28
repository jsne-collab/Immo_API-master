<?php

namespace Tests\Feature\Property;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_with_an_active_lease_can_view_their_rented_property(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->rented()->create();
        Lease::factory()->create(['tenant_id' => $tenant->id, 'property_id' => $property->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk()->assertJsonPath('data.id', $property->id);
    }

    public function test_a_tenant_without_a_lease_cannot_view_a_rented_property(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->rented()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/properties/{$property->id}");

        $response->assertStatus(403);
    }

    public function test_an_owner_can_view_their_own_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->maintenance()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk()->assertJsonPath('data.id', $property->id);
    }

    public function test_any_authenticated_user_can_view_an_available_property(): void
    {
        $tenant = User::factory()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk()->assertJsonPath('data.id', $property->id);
    }

    public function test_a_user_cannot_view_another_owners_non_available_property(): void
    {
        $tenant = User::factory()->create();
        $property = Property::factory()->maintenance()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/properties/{$property->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_for_an_unknown_property(): void
    {
        $tenant = User::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/properties/999999');

        $response->assertStatus(404);
    }

    public function test_show_requires_authentication(): void
    {
        $property = Property::factory()->create();

        $response = $this->getJson("/api/v1/properties/{$property->id}");

        $response->assertStatus(401);
    }
}
