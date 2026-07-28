<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAndDeletePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_validate_a_pending_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $payment = Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/payments/{$payment->id}", [
            'status' => 'validated',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'validated');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'validated']);
    }

    public function test_a_tenant_cannot_update_the_payment(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $payment = Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->putJson("/api/v1/payments/{$payment->id}", [
            'status' => 'validated',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_requires_authentication(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->putJson("/api/v1/payments/{$payment->id}", ['status' => 'validated']);

        $response->assertStatus(401);
    }

    public function test_an_owner_can_delete_a_pending_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $payment = Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_an_owner_cannot_delete_a_validated_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id, 'status' => 'validated']);

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_a_tenant_cannot_delete_the_payment(): void
    {
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id]);
        $payment = Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($tenant, 'sanctum')->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(403);
    }
}
