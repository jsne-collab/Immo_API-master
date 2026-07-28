<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_refresh_their_token(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $newToken = $response->json('data.token');
        $this->assertNotSame($oldToken, $newToken);

        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_refresh_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401)->assertJsonPath('success', false);
    }
}
