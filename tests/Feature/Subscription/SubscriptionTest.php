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

    public function test_subscription_endpoint_exposes_monthly_and_yearly_plans(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('data.plans.monthly.amount', 10000)
            ->assertJsonPath('data.plans.yearly.amount', 50000);
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
            'method_provider' => 'T-Money',
        ]);

        // Sans "plan" précisé, la formule mensuelle (10 000 FCFA) s'applique
        // par défaut (config('subscription.default_plan')).
        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.plan', 'monthly')
            ->assertJsonPath('data.amount', 10000);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id' => $owner->id,
            'status' => Subscription::STATUS_PENDING,
            'plan' => 'monthly',
        ]);
    }

    public function test_owner_can_initiate_a_yearly_subscription_payment(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->postJson('/api/v1/subscription/initiate', [
            'plan' => 'yearly',
            'method_type' => 'mobile_money',
            'method_provider' => 'Moov Money',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.plan', 'yearly')
            ->assertJsonPath('data.amount', 50000);

        $subscription = Subscription::where('owner_id', $owner->id)->firstOrFail();
        $this->assertEquals(
            $subscription->period_start->copy()->addMonths(12)->toDateString(),
            $subscription->period_end->toDateString(),
        );
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
