<?php

namespace Tests\Feature\ActivityLog;

use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_logged(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koffi',
            'email' => 'awa@example.test',
            'phone' => '+22890001111',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ])->assertCreated();

        $user = User::where('email', 'awa@example.test')->first();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityLog::ACTION_REGISTER,
        ]);
    }

    public function test_successful_login_is_logged(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password!234')]);

        $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'Password!234',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityLog::ACTION_LOGIN,
        ]);
    }

    public function test_failed_login_is_logged(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password!234')]);

        $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityLog::ACTION_LOGIN_FAILED,
        ]);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityLog::ACTION_LOGOUT,
        ]);
    }

    public function test_lease_creation_is_logged(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/leases', [
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 100000,
            'deposit_amount' => 100000,
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $owner->id,
            'action' => ActivityLog::ACTION_LEASE_CREATED,
        ]);
    }

    public function test_lease_termination_is_logged(): void
    {
        $lease = Lease::factory()->create();

        $this->actingAs($lease->owner, 'sanctum')->postJson("/api/v1/leases/{$lease->id}/terminate", [])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $lease->owner_id,
            'action' => ActivityLog::ACTION_LEASE_TERMINATED,
        ]);
    }

    public function test_payment_validation_is_logged(): void
    {
        $lease = Lease::factory()->create();

        $this->actingAs($lease->owner, 'sanctum')->postJson('/api/v1/payments', [
            'lease_id' => $lease->id,
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'period_covered' => '2026-07',
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $lease->owner_id,
            'action' => ActivityLog::ACTION_PAYMENT_VALIDATED,
        ]);
    }
}
