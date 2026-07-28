<?php

namespace Tests\Feature\User;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_upload_their_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $path = $user->fresh()->profile->avatar;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_uploading_a_new_avatar_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['avatar' => 'avatars/old.jpg']);
        Storage::disk('public')->put('avatars/old.jpg', 'fake-content');

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg'),
        ])->assertOk();

        Storage::disk('public')->assertMissing('avatars/old.jpg');
    }

    public function test_avatar_upload_fails_with_a_non_image_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['avatar']);
    }

    public function test_a_user_cannot_upload_an_avatar_for_another_user(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/users/{$other->id}/avatar", [
            'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(403);
    }
}
