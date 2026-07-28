<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_owner_dashboard_returns_correct_aggregated_figures(): void
    {
        Carbon::setTestNow('2026-07-19');

        $owner = User::factory()->owner()->create();
        $property = Property::factory()->rented()->create(['owner_id' => $owner->id]);
        $lease = Lease::factory()->create(['owner_id' => $owner->id, 'property_id' => $property->id]);

        // Revenus validés ce mois-ci.
        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'status' => 'validated',
            'amount' => 100000,
            'payment_date' => '2026-07-10',
        ]);
        // Revenus validés le mois précédent (pour la variation).
        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'status' => 'validated',
            'amount' => 50000,
            'payment_date' => '2026-06-10',
        ]);
        // Paiement en attente.
        Payment::factory()->pending()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'amount' => 20000,
        ]);

        Property::factory()->create(['owner_id' => $owner->id, 'status' => 'available']);
        Property::factory()->create(['owner_id' => $owner->id, 'status' => 'available']);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/owner');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals(100000.0, $data['monthly_revenue']);
        $this->assertEquals(50000.0, $data['previous_month_revenue']);
        $this->assertEquals(100.0, $data['revenue_variation_percent']);
        $this->assertSame(1, $data['pending_payments_count']);
        $this->assertSame(2, $data['available_properties_count']);
        // 1 bien loué (celui du bail) + 2 disponibles = 3 biens, 1 loué -> 33.3%.
        $this->assertEquals(33.3, $data['occupancy_rate']);
        $this->assertCount(3, $data['recent_payments']);
    }

    public function test_a_tenant_cannot_access_the_owner_dashboard(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/dashboard/owner');

        $response->assertStatus(403);
    }

    public function test_owner_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard/owner');

        $response->assertStatus(401);
    }

    public function test_owner_dashboard_cache_is_invalidated_when_a_new_payment_is_recorded(): void
    {
        Carbon::setTestNow('2026-07-19');

        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $first = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/owner');
        $this->assertEquals(0.0, $first->json('data.monthly_revenue'));

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 60000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ])->assertCreated();

        $second = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/owner');
        $this->assertEquals(60000.0, $second->json('data.monthly_revenue'));
    }
}
