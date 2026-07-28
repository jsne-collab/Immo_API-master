<?php

namespace Tests\Feature\PropertyUnit;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyUnitCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_list_units_of_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        PropertyUnit::factory()->count(3)->for($property)->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/properties/{$property->id}/units");

        $response->assertOk();
        $this->assertCount(3, $response->json('data.items'));
    }

    public function test_a_user_cannot_list_units_of_another_owners_non_available_property(): void
    {
        $tenant = User::factory()->create();
        $property = Property::factory()->maintenance()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/properties/{$property->id}/units");

        $response->assertStatus(403);
    }

    public function test_an_owner_can_create_a_unit_for_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/units", [
            'unit_name' => 'Appartement A1',
            'floor' => '1',
            'rooms_count' => 2,
            'monthly_rent' => 75000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.unit_name', 'Appartement A1')
            ->assertJsonPath('data.status', 'available');

        $this->assertDatabaseHas('property_units', ['property_id' => $property->id, 'unit_name' => 'Appartement A1']);
    }

    public function test_a_user_cannot_create_a_unit_for_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/units", [
            'unit_name' => 'Hacked Unit',
            'rooms_count' => 1,
            'monthly_rent' => 10000,
        ]);

        $response->assertStatus(403);
    }

    public function test_store_unit_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/units", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['unit_name', 'rooms_count', 'monthly_rent']);
    }

    public function test_an_owner_can_view_their_unit(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = PropertyUnit::factory()->for($property)->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/units/{$unit->id}");

        $response->assertOk()->assertJsonPath('data.id', $unit->id);
    }

    public function test_an_owner_can_update_their_unit(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = PropertyUnit::factory()->for($property)->create(['unit_name' => 'Old Name']);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/units/{$unit->id}", [
            'unit_name' => 'New Name',
            'status' => 'maintenance',
        ]);

        $response->assertOk()->assertJsonPath('data.unit_name', 'New Name');
        $this->assertDatabaseHas('property_units', ['id' => $unit->id, 'unit_name' => 'New Name']);
    }

    public function test_a_user_cannot_update_a_unit_of_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $unit = PropertyUnit::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/units/{$unit->id}", [
            'unit_name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_an_owner_can_delete_their_unit(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = PropertyUnit::factory()->for($property)->create();

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/units/{$unit->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('property_units', ['id' => $unit->id]);
    }

    public function test_a_user_cannot_delete_a_unit_of_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $unit = PropertyUnit::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/units/{$unit->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('property_units', ['id' => $unit->id]);
    }

    public function test_unit_endpoints_require_authentication(): void
    {
        $unit = PropertyUnit::factory()->create();

        $this->getJson("/api/v1/units/{$unit->id}")->assertStatus(401);
        $this->putJson("/api/v1/units/{$unit->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/units/{$unit->id}")->assertStatus(401);
    }
}
