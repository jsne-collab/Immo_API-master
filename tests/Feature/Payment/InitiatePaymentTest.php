<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiatePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_initiate_a_payment_for_their_active_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'period_covered' => '2026-07',
            'method_type' => 'mobile_money',
            'method_provider' => 'Flooz',
            'method_account_number' => '+22890000000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_method.type', 'mobile_money');

        $this->assertDatabaseHas('payments', ['lease_id' => $lease->id, 'tenant_id' => $tenant->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payment_methods', ['user_id' => $tenant->id, 'type' => 'mobile_money']);
    }

    public function test_initiate_reuses_an_existing_payment_method_when_given_its_id(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $method = PaymentMethod::factory()->create(['user_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 50000,
            'period_covered' => '2026-07',
            'payment_method_id' => $method->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.payment_method.id', $method->id);
        $this->assertDatabaseCount('payment_methods', 1);
    }

    public function test_a_tenant_cannot_initiate_a_payment_for_another_tenants_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'period_covered' => '2026-07',
            'method_type' => 'cash',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lease_id']);
    }

    public function test_an_owner_cannot_initiate_a_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'period_covered' => '2026-07',
            'method_type' => 'cash',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_initiate_a_payment_for_a_non_active_lease(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->terminated()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'period_covered' => '2026-07',
            'method_type' => 'cash',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lease_id']);
    }

    public function test_initiate_fails_with_missing_fields(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['lease_id', 'amount', 'period_covered']);
    }

    public function test_initiate_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/payments/initiate', []);

        $response->assertStatus(401);
    }
}
