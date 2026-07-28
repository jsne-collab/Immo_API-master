<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeletePropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_delete_their_own_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        Storage::disk('public')->put('properties/photo.jpg', 'fake-content');
        PropertyImage::factory()->for($property)->create(['image_path' => 'properties/photo.jpg']);

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/properties/{$property->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('property_images', ['property_id' => $property->id]);
        Storage::disk('public')->assertMissing('properties/photo.jpg');
    }

    public function test_a_user_cannot_delete_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/properties/{$property->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('properties', ['id' => $property->id]);
    }

    public function test_delete_requires_authentication(): void
    {
        $property = Property::factory()->create();

        $response = $this->deleteJson("/api/v1/properties/{$property->id}");

        $response->assertStatus(401);
    }
}
