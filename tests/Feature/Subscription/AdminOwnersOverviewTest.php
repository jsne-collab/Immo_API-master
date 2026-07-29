<?php

namespace Tests\Feature\Subscription;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminOwnersOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_sees_every_owner_with_or_without_tenants_and_subscription_status(): void
    {
        Carbon::setTestNow('2026-07-29');

        $admin = User::factory()->admin()->create();

        // Propriétaire avec un locataire actif et un abonnement à jour.
        $ownerWithTenant = User::factory()->owner()->create(['name' => 'Owner With Tenant']);
        $property = Property::factory()->rented()->create(['owner_id' => $ownerWithTenant->id]);
        Lease::factory()->create([
            'owner_id' => $ownerWithTenant->id,
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        Subscription::factory()->create([
            'owner_id' => $ownerWithTenant->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-08-01',
        ]);

        // Propriétaire sans aucun locataire, jamais payé.
        $ownerWithoutTenant = User::factory()->owner()->create(['name' => 'Owner Without Tenant']);
        Property::factory()->create(['owner_id' => $ownerWithoutTenant->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owners');

        $response->assertOk();

        $payload = collect($response->json('data'));

        $withTenantRow = $payload->firstWhere('owner.id', $ownerWithTenant->id);
        $this->assertSame(1, $withTenantRow['tenant_count']);
        $this->assertSame(1, $withTenantRow['properties_count']);
        $this->assertSame('paid', $withTenantRow['subscription_status']);

        $withoutTenantRow = $payload->firstWhere('owner.id', $ownerWithoutTenant->id);
        $this->assertSame(0, $withoutTenantRow['tenant_count']);
        $this->assertSame(1, $withoutTenantRow['properties_count']);
        $this->assertSame('never', $withoutTenantRow['subscription_status']);
    }

    public function test_owner_cannot_access_the_admin_overview(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->getJson('/api/v1/admin/owners')->assertForbidden();
    }

    public function test_admin_sees_owner_detail_with_tenants_and_subscription_history(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        Subscription::factory()->pending()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/admin/owners/{$owner->id}");

        $response->assertOk();
        $response->assertJsonPath('data.owner.id', $owner->id);
        $response->assertJsonPath('data.subscription_status', 'pending');
        $response->assertJsonCount(1, 'data.leases');
        $response->assertJsonPath('data.leases.0.tenant.id', $lease->tenant_id);
        $response->assertJsonCount(1, 'data.subscriptions');
    }

    public function test_owner_cannot_access_another_owners_detail(): void
    {
        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->getJson("/api/v1/admin/owners/{$otherOwner->id}")
            ->assertForbidden();
    }

    public function test_owner_detail_404s_for_a_non_owner_user(): void
    {
        $admin = User::factory()->admin()->create();
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/owners/{$tenant->id}")
            ->assertNotFound();
    }
}
