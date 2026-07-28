<?php

namespace Tests\Feature\Receipt;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function receiptFor(User $owner, User $tenant): Receipt
    {
        $lease = Lease::factory()->create(['owner_id' => $owner->id, 'tenant_id' => $tenant->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);

        return Receipt::factory()->create(['payment_id' => $payment->id]);
    }

    public function test_an_owner_sees_receipts_for_their_leases(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $this->receiptFor($owner, $tenant);
        $this->receiptFor(User::factory()->owner()->create(), User::factory()->tenant()->create());

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/receipts');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_a_tenant_sees_only_their_own_receipts(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $this->receiptFor($owner, $tenant);
        $this->receiptFor($owner, User::factory()->tenant()->create());

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/receipts');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/receipts');

        $response->assertStatus(401);
    }

    public function test_the_owner_can_view_the_receipt(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $receipt = $this->receiptFor($owner, $tenant);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $receipt->id)
            ->assertJsonPath('data.receipt_number', $receipt->receipt_number);
    }

    public function test_the_tenant_can_view_their_receipt(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $receipt = $this->receiptFor($owner, $tenant);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}");

        $response->assertOk()->assertJsonPath('data.id', $receipt->id);
    }

    public function test_an_unrelated_user_cannot_view_the_receipt(): void
    {
        $stranger = User::factory()->create();
        $receipt = $this->receiptFor(User::factory()->owner()->create(), User::factory()->tenant()->create());

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}");

        $response->assertStatus(403);
    }
}
