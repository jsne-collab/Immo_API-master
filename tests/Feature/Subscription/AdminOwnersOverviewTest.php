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
}
