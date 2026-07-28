<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryAndStatsPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_returns_the_users_payments(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->count(3)->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/payments/history');

        $response->assertOk();
        $this->assertCount(3, $response->json('data.items'));
    }

    public function test_history_filters_by_date_range(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'payment_date' => now()->subMonths(6)->toDateString(),
        ]);
        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson(
            '/api/v1/payments/history?from='.now()->subMonth()->toDateString()
        );

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/payments/history');

        $response->assertStatus(401);
    }

    public function test_stats_aggregates_amounts_by_status(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id, 'status' => 'validated', 'amount' => 100000]);
        Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id, 'amount' => 50000]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/payments/stats');

        $response->assertOk()
            ->assertJsonPath('data.total_validated', 100000)
            ->assertJsonPath('data.total_pending', 50000)
            ->assertJsonPath('data.count_by_status.validated', 1)
            ->assertJsonPath('data.count_by_status.pending', 1);
    }

    public function test_stats_only_counts_the_users_own_payments(): void
    {
        $owner = User::factory()->owner()->create();
        Payment::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/payments/stats');

        $response->assertOk()->assertJsonPath('data.total_validated', 0);
    }

    public function test_stats_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/payments/stats');

        $response->assertStatus(401);
    }
}
