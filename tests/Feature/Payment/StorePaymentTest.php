<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_record_a_payment_for_their_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
            'reference' => 'CASH-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'validated');

        $this->assertDatabaseHas('payments', ['lease_id' => $lease->id, 'reference' => 'CASH-001']);
    }

    public function test_an_owner_can_record_a_pending_payment_explicitly(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
            'status' => 'pending',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_a_tenant_cannot_record_a_payment_directly(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ]);

        $response->assertStatus(403);
    }

    public function test_an_owner_cannot_record_a_payment_for_another_owners_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lease_id']);
    }

    public function test_cannot_record_a_payment_for_a_non_active_lease(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->expired()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lease_id']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', []);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'lease_id', 'amount', 'payment_date', 'period_covered',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/payments', []);

        $response->assertStatus(401);
    }
}
