<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndAvailablePropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_only_lists_available_properties(): void
    {
        $tenant = User::factory()->create();
        Property::factory()->count(2)->create();
        Property::factory()->rented()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/properties/available');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_search_filters_by_city(): void
    {
        $tenant = User::factory()->create();
        Property::factory()->create(['city' => 'Lomé']);
        Property::factory()->create(['city' => 'Kara']);

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/properties/search?city=Lom');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame('Lomé', $response->json('data.items.0.city'));
    }

    public function test_search_filters_by_price_range(): void
    {
        $tenant = User::factory()->create();
        Property::factory()->create(['monthly_rent' => 50000]);
        Property::factory()->create(['monthly_rent' => 300000]);

        $response = $this->actingAs($tenant, 'sanctum')
            ->getJson('/api/v1/properties/search?min_price=100000&max_price=400000');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_search_excludes_non_available_properties(): void
    {
        $tenant = User::factory()->create();
        Property::factory()->rented()->create(['city' => 'Lomé']);

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/properties/search?city=Lomé');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.items'));
    }

    public function test_available_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/properties/available');

        $response->assertStatus(401);
    }
}
