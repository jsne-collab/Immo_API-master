<?php

namespace Tests\Feature\Property;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePropertyTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Villa Bord de Mer',
            'type' => 'maison',
            'address' => '10 Rue des Palmiers',
            'city' => 'Lomé',
            'surface_area' => 150,
            'rooms_count' => 4,
            'monthly_rent' => 250000,
            'deposit_amount' => 250000,
            'description' => 'Belle villa avec jardin.',
        ], $overrides);
    }

    public function test_an_owner_can_create_a_property(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/properties', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Villa Bord de Mer')
            ->assertJsonPath('data.status', 'available')
            ->assertJsonPath('data.owner_id', $owner->id);

        $this->assertDatabaseHas('properties', ['title' => 'Villa Bord de Mer', 'owner_id' => $owner->id]);
    }

    public function test_a_tenant_cannot_create_a_property(): void
    {
        $tenant = User::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/properties', $this->payload());

        $response->assertStatus(403);
        $this->assertDatabaseMissing('properties', ['title' => 'Villa Bord de Mer']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/properties', []);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'title', 'type', 'address', 'city', 'rooms_count', 'monthly_rent', 'deposit_amount',
        ]);
    }

    public function test_store_fails_with_an_invalid_type(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/properties', $this->payload(['type' => 'chateau']));

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/properties', $this->payload());

        $response->assertStatus(401);
    }
}
