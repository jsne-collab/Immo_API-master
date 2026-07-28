<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Support\LeaseDueDateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tenant_dashboard_returns_the_active_lease_and_next_due_date(): void
    {
        Carbon::setTestNow('2026-07-19');

        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create([
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-24',
        ]);
        Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/dashboard/tenant');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame($lease->id, $data['current_lease']['id']);
        $this->assertSame(
            LeaseDueDateCalculator::nextDueDate($lease)->toDateString(),
            $data['next_due_date'],
        );
        $this->assertCount(1, $data['recent_payments']);
    }

    public function test_tenant_dashboard_returns_null_lease_when_no_active_lease(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/dashboard/tenant');

        $response->assertOk();
        $this->assertNull($response->json('data.current_lease'));
        $this->assertNull($response->json('data.next_due_date'));
    }

    public function test_an_owner_cannot_access_the_tenant_dashboard(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard/tenant');

        $response->assertStatus(403);
    }

    public function test_tenant_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard/tenant');

        $response->assertStatus(401);
    }
}
