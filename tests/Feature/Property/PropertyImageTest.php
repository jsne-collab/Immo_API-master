<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_upload_an_image_to_their_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/images", [
            'image' => UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
            'is_primary' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('property_images', ['property_id' => $property->id, 'is_primary' => true]);
    }

    public function test_setting_a_new_primary_image_unsets_the_previous_one(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $property->images()->create(['image_path' => 'properties/first.jpg', 'is_primary' => true]);

        $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/images", [
            'image' => UploadedFile::fake()->create('second.jpg', 100, 'image/jpeg'),
            'is_primary' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('property_images', ['image_path' => 'properties/first.jpg', 'is_primary' => false]);
        $this->assertDatabaseCount('property_images', 2);
    }

    public function test_a_user_cannot_upload_an_image_to_another_owners_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/images", [
            'image' => UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(403);
    }

    public function test_upload_image_fails_with_a_non_image_file(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/properties/{$property->id}/images", [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['image']);
    }

    public function test_an_owner_can_delete_an_image_from_their_property(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        Storage::disk('public')->put('properties/photo.jpg', 'fake-content');
        $image = $property->images()->create(['image_path' => 'properties/photo.jpg', 'is_primary' => false]);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/properties/{$property->id}/images/{$image->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('property_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('properties/photo.jpg');
    }

    public function test_deleting_an_image_that_does_not_belong_to_the_property_fails(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $otherProperty = Property::factory()->create();
        $image = $otherProperty->images()->create(['image_path' => 'properties/other.jpg']);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/properties/{$property->id}/images/{$image->id}");

        $response->assertStatus(422);
    }

    public function test_a_user_cannot_delete_an_image_from_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();
        $image = $property->images()->create(['image_path' => 'properties/photo.jpg']);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/properties/{$property->id}/images/{$image->id}");

        $response->assertStatus(403);
    }
}
