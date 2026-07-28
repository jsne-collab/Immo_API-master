<?php

namespace Tests\Feature\Receipt;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_get_the_receipt_download_url(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id, 'tenant_id' => $tenant->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}/download");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.url'));
        $this->assertStringContainsString($receipt->pdf_path, $response->json('data.url'));
    }

    public function test_the_tenant_can_get_the_receipt_download_url(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['owner_id' => $owner->id, 'tenant_id' => $tenant->id]);
        $payment = Payment::factory()->create(['lease_id' => $lease->id, 'tenant_id' => $tenant->id]);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id]);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}/download");

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_an_unrelated_user_cannot_download_the_receipt(): void
    {
        $stranger = User::factory()->create();
        $receipt = Receipt::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/receipts/{$receipt->id}/download");

        $response->assertStatus(403);
    }

    public function test_download_requires_authentication(): void
    {
        $receipt = Receipt::factory()->create();

        $response = $this->getJson("/api/v1/receipts/{$receipt->id}/download");

        $response->assertStatus(401);
    }
}
