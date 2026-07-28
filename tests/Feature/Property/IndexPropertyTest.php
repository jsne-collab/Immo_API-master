<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_sees_only_their_own_properties(): void
    {
        $owner = User::factory()->owner()->create();
        Property::factory()->count(2)->create(['owner_id' => $owner->id]);
        Property::factory()->count(3)->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_a_tenant_sees_an_empty_list(): void
    {
        $tenant = User::factory()->create();
        Property::factory()->count(2)->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/properties');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.items'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/properties');

        $response->assertStatus(401);
    }
}
