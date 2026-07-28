<?php

namespace Tests\Unit\Services;

use App\Models\Lease;
use App\Models\Notification;
use App\Services\PaymentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sends_a_reminder_when_the_due_date_is_within_the_configured_window(): void
    {
        config(['reminders.payment_reminder_days_before' => 5]);
        Carbon::setTestNow('2026-07-19');

        // Bail débuté le 24 du mois -> prochaine échéance le 24/07, soit
        // dans 5 jours par rapport à "aujourd'hui" (19/07).
        $lease = Lease::factory()->create([
            'start_date' => '2026-01-24',
            'end_date' => '2027-01-24',
        ]);

        $count = app(PaymentReminderService::class)->sendDueReminders();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $lease->tenant_id,
            'type' => Notification::TYPE_PAYMENT_REMINDER,
        ]);
    }

    public function test_does_not_send_a_reminder_outside_the_window(): void
    {
        config(['reminders.payment_reminder_days_before' => 5]);
        Carbon::setTestNow('2026-07-19');

        Lease::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-01',
        ]);

        $count = app(PaymentReminderService::class)->sendDueReminders();

        $this->assertSame(0, $count);
    }

    public function test_does_not_send_a_duplicate_reminder_for_the_same_due_date(): void
    {
        config(['reminders.payment_reminder_days_before' => 5]);
        Carbon::setTestNow('2026-07-19');

        Lease::factory()->create([
            'start_date' => '2026-01-24',
            'end_date' => '2027-01-24',
        ]);

        $service = app(PaymentReminderService::class);
        $first = $service->sendDueReminders();
        $second = $service->sendDueReminders();

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
    }

    public function test_ignores_non_active_leases(): void
    {
        config(['reminders.payment_reminder_days_before' => 5]);
        Carbon::setTestNow('2026-07-19');

        Lease::factory()->terminated()->create([
            'start_date' => '2026-01-24',
        ]);

        $count = app(PaymentReminderService::class)->sendDueReminders();

        $this->assertSame(0, $count);
    }
}
