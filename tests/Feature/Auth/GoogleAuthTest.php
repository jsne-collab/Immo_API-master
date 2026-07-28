<?php

namespace Tests\Feature\Auth;

use App\Contracts\GoogleIdTokenVerifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeVerifier(?array $payload): void
    {
        $this->app->bind(GoogleIdTokenVerifier::class, fn () => new class($payload) implements GoogleIdTokenVerifier
        {
            public function __construct(private readonly ?array $payload) {}

            public function verify(string $idToken): ?array
            {
                return $this->payload;
            }
        });
    }

    public function test_an_invalid_google_token_is_rejected(): void
    {
        $this->fakeVerifier(null);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'forged-token']);

        $response->assertStatus(401);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_new_google_user_is_created_with_an_incomplete_profile(): void
    {
        $this->fakeVerifier([
            'sub' => 'google-sub-123',
            'email' => 'nouveau@example.com',
            'name' => 'Nouveau Utilisateur',
            'picture' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'valid-token']);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'nouveau@example.com')
            ->assertJsonPath('data.user.profile_completed', false)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $user = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertFalse((bool) $user->profile_completed);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_id_is_never_exposed_in_the_response(): void
    {
        $this->fakeVerifier([
            'sub' => 'google-sub-999',
            'email' => 'hidden@example.com',
            'name' => 'Hidden',
            'picture' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'valid-token']);

        $response->assertOk();
        $this->assertArrayNotHasKey('google_id', $response->json('data.user'));
    }

    public function test_an_existing_password_user_signing_in_with_google_links_the_same_account(): void
    {
        $existing = User::factory()->create(['email' => 'deja-inscrit@example.com']);

        $this->fakeVerifier([
            'sub' => 'google-sub-456',
            'email' => 'deja-inscrit@example.com',
            'name' => 'Déjà Inscrit',
            'picture' => 'https://example.com/pic.jpg',
        ]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'valid-token']);

        $response->assertOk()->assertJsonPath('data.user.id', $existing->id);

        $this->assertDatabaseCount('users', 1);
        $existing->refresh();
        $this->assertSame('google-sub-456', $existing->google_id);
        $this->assertTrue((bool) $existing->profile_completed);
    }

    public function test_a_returning_google_user_is_found_by_google_id(): void
    {
        $user = User::factory()->create([
            'email' => 'retour@example.com',
            'google_id' => 'google-sub-789',
        ]);

        $this->fakeVerifier([
            'sub' => 'google-sub-789',
            'email' => 'retour@example.com',
            'name' => 'Retour',
            'picture' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'valid-token']);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_a_protected_route_is_blocked_while_the_profile_is_incomplete(): void
    {
        $user = User::factory()->create(['profile_completed' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_complete_profile_route_remains_accessible_with_an_incomplete_profile(): void
    {
        $user = User::factory()->create(['profile_completed' => false, 'phone' => null]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/complete-profile', [
            'role' => 'tenant',
            'phone' => '+22890001234',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.profile_completed', true);
    }
}
