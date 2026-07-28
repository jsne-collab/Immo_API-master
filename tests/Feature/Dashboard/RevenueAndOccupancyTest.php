<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RevenueAndOccupancyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_revenue_series_covers_six_months_with_zeros_for_months_without_payments(): void
    {
        Carbon::setTestNow('2026-07-19');

        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'status' => 'validated',
            'amount' => 80000,
            'payment_date' => '2026-07-05',
        ]);
        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'status' => 'validated',
            'amount' => 40000,
            'payment_date' => '2026-05-05',
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/revenue');

        $response->assertOk();
        $series = $response->json('data');

        $this->assertCount(6, $series);
        $this->assertSame('2026-02', $series[0]['month']);
        $this->assertSame('2026-07', $series[5]['month']);
        $this->assertEquals(80000.0, $series[5]['total']);
        $this->assertEquals(40000.0, $series[3]['total']);
        $this->assertEquals(0.0, $series[4]['total']);
    }

    public function test_a_tenant_cannot_access_the_revenue_series(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/dashboard/revenue');

        $response->assertStatus(403);
    }

    public function test_occupancy_returns_the_overall_rate_and_the_properties_breakdown(): void
    {
        $owner = User::factory()->owner()->create();
        Property::factory()->create(['owner_id' => $owner->id, 'status' => 'rented']);
        Property::factory()->create(['owner_id' => $owner->id, 'status' => 'available']);
        Property::factory()->create(['owner_id' => $owner->id, 'status' => 'available']);
        Property::factory()->create(); // autre propriétaire, doit être ignoré

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/occupancy');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals(33.3, $data['overall_rate']);
        $this->assertCount(3, $data['properties']);
    }

    public function test_a_tenant_cannot_access_occupancy(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/dashboard/occupancy');

        $response->assertStatus(403);
    }
}
