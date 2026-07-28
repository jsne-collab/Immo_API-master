<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_get_the_contract_download_url(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $create = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', [
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 100000,
            'deposit_amount' => 100000,
        ]);
        $leaseId = $create->json('data.id');

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/leases/{$leaseId}/download");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.url'));
    }

    public function test_the_tenant_can_get_the_contract_download_url(): void
    {
        Storage::fake('public');
        $tenant = User::factory()->tenant()->create();
        $lease = Lease::factory()->create(['tenant_id' => $tenant->id, 'contract_pdf_path' => 'leases/x.pdf']);

        $response = $this->actingAs($tenant, 'sanctum')->getJson("/api/v1/leases/{$lease->id}/download");

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_an_unrelated_user_cannot_download_the_contract(): void
    {
        $stranger = User::factory()->create();
        $lease = Lease::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/leases/{$lease->id}/download");

        $response->assertStatus(403);
    }

    public function test_download_requires_authentication(): void
    {
        $lease = Lease::factory()->create();

        $response = $this->getJson("/api/v1/leases/{$lease->id}/download");

        $response->assertStatus(401);
    }
}
