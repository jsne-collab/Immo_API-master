<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_owner_with_no_subscription_history_is_never_status(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->getJson('/api/v1/subscription');

        $response->assertOk()->assertJsonPath('data.status', 'never');
    }

    public function test_tenant_cannot_see_owner_subscription_endpoint(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->getJson('/api/v1/subscription')->assertForbidden();
    }

    public function test_owner_can_initiate_a_subscription_payment(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->postJson('/api/v1/subscription/initiate', [
            'method_type' => 'mobile_money',
            'method_provider' => 'Flooz',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('subscriptions', [
            'owner_id' => $owner->id,
            'status' => Subscription::STATUS_PENDING,
        ]);
    }

    public function test_tenant_cannot_initiate_a_subscription_payment(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->postJson('/api/v1/subscription/initiate', [
            'method_type' => 'mobile_money',
        ])->assertForbidden();
    }

    public function test_admin_can_validate_a_pending_subscription(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $subscription = Subscription::factory()->pending()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/subscriptions/{$subscription->id}/validate");

        $response->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertNotNull($subscription->fresh()->paid_at);
    }

    public function test_owner_cannot_validate_their_own_subscription(): void
    {
        $owner = User::factory()->owner()->create();
        $subscription = Subscription::factory()->pending()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->putJson("/api/v1/admin/subscriptions/{$subscription->id}/validate")
            ->assertForbidden();
    }

    public function test_owner_with_expired_paid_subscription_is_overdue(): void
    {
        Carbon::setTestNow('2026-07-29');

        $owner = User::factory()->owner()->create();
        Subscription::factory()->expired()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->getJson('/api/v1/subscription');

        $response->assertOk()->assertJsonPath('data.status', 'overdue');
    }

    public function test_owner_with_current_paid_subscription_is_up_to_date(): void
    {
        Carbon::setTestNow('2026-07-29');

        $owner = User::factory()->owner()->create();
        Subscription::factory()->create([
            'owner_id' => $owner->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-08-01',
        ]);

        $response = $this->actingAs($owner)->getJson('/api/v1/subscription');

        $response->assertOk()->assertJsonPath('data.status', 'paid');
    }
}
