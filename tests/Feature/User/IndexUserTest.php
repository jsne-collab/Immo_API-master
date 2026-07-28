<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertCount(4, $response->json('data.items'));
        $this->assertSame(4, $response->json('data.pagination.total'));
    }

    public function test_a_tenant_cannot_list_users(): void
    {
        $tenant = User::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_an_owner_can_search_a_tenant_by_phone(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->create(['phone' => '+22890001122']);
        User::factory()->count(2)->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/users?search=90001122');

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame($tenant->id, $items[0]['id']);
    }

    public function test_an_owner_search_never_returns_non_tenant_accounts(): void
    {
        $owner = User::factory()->owner()->create();
        User::factory()->owner()->create(['name' => 'Autre Proprio']);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/users?search=Autre');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.items'));
    }

    public function test_an_owner_must_provide_a_search_term(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/users');

        $response->assertStatus(422)->assertJsonValidationErrors(['search']);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }
}
