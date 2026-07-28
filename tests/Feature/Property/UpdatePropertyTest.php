<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_update_their_own_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'title' => 'Old Title']);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/properties/{$property->id}", [
            'title' => 'New Title',
            'status' => 'maintenance',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonPath('data.status', 'maintenance');

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'title' => 'New Title']);
    }

    public function test_a_user_cannot_update_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/properties/{$property->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('properties', ['id' => $property->id, 'title' => 'Hacked']);
    }

    public function test_update_fails_with_an_invalid_status(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/properties/{$property->id}", [
            'status' => 'sold',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_update_requires_authentication(): void
    {
        $property = Property::factory()->create();

        $response = $this->putJson("/api/v1/properties/{$property->id}", ['title' => 'X']);

        $response->assertStatus(401);
    }
}
