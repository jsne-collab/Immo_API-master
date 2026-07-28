<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_sees_payments_for_their_leases(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->count(2)->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);
        Payment::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/payments');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_a_tenant_sees_only_their_own_payments(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);
        Payment::factory()->count(2)->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/payments');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_index_can_filter_by_status(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id, 'status' => 'validated']);
        Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/payments?status=pending');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_index_can_filter_by_property_id(): void
    {
        $owner = User::factory()->owner()->create();
        $leaseA = Lease::factory()->create(['owner_id' => $owner->id]);
        $leaseB = Lease::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->create(['lease_id' => $leaseA->id, 'tenant_id' => $leaseA->tenant_id]);
        Payment::factory()->create(['lease_id' => $leaseB->id, 'tenant_id' => $leaseB->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/payments?property_id={$leaseA->property_id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame($leaseA->id, $response->json('data.items.0.lease.id'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertStatus(401);
    }

    public function test_the_owner_can_view_the_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()->assertJsonPath('data.id', $payment->id);
    }

    public function test_the_tenant_can_view_their_payment(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()->assertJsonPath('data.id', $payment->id);
    }

    public function test_an_unrelated_user_cannot_view_the_payment(): void
    {
        $stranger = User::factory()->create();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(403);
    }
}
