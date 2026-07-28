<?php

namespace Tests\Feature\Payment;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Règle A.6.4 : la quittance est générée automatiquement uniquement
 * pour un paiement "validated" — vérifiée ici au niveau HTTP (le
 * pipeline complet : requête -> PaymentService -> job -> quittance).
 */
class PaymentValidationGeneratesReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_validated_payment_generates_a_receipt(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ]);

        $response->assertCreated();
        $paymentId = $response->json('data.id');
        $this->assertDatabaseHas('receipts', ['payment_id' => $paymentId]);
    }

    public function test_recording_a_pending_payment_does_not_generate_a_receipt(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
            'status' => 'pending',
        ]);

        $response->assertCreated();
        $this->assertDatabaseMissing('receipts', ['payment_id' => $response->json('data.id')]);
    }

    public function test_validating_a_pending_payment_generates_a_receipt(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id]);
        $payment = Payment::factory()->pending()->create(['lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id]);

        $this->assertDatabaseMissing('receipts', ['payment_id' => $payment->id]);

        $this->actingAs($owner, 'sanctum')->putJson("/api/v1/payments/{$payment->id}", [
            'status' => 'validated',
        ])->assertOk();

        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);
    }

    public function test_a_tenants_initiated_payment_only_gets_a_receipt_once_validated(): void
    {
        Storage::fake('public');
        $tenant = User::factory()->tenant()->create();
        $owner = User::factory()->owner()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id, 'owner_id' => $owner->id]);

        $initiate = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'lease_id' => $lease->id,
            'amount' => 100000,
            'period_covered' => '2026-07',
            'method_type' => 'cash',
        ]);
        $paymentId = $initiate->json('data.id');

        $this->assertDatabaseMissing('receipts', ['payment_id' => $paymentId]);

        $this->actingAs($owner, 'sanctum')->putJson("/api/v1/payments/{$paymentId}", [
            'status' => 'validated',
        ])->assertOk();

        $this->assertDatabaseHas('receipts', ['payment_id' => $paymentId]);
    }
}
