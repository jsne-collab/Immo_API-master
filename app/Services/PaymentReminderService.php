<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Notification;
use App\Support\LeaseDueDateCalculator;
use Carbon\Carbon;

class PaymentReminderService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Règle A.6.7 : envoie un rappel de paiement au locataire lorsque
     * l'échéance mensuelle du bail (jour du mois de sa date de début)
     * tombe dans `payment_reminder_days_before` jours. Un rappel n'est
     * envoyé qu'une fois par échéance (déduplication via le contenu du
     * message, qui inclut la date d'échéance).
     */
    public function sendDueReminders(): int
    {
        $reminderDays = (int) config('reminders.payment_reminder_days_before');
        $today = Carbon::today();
        $sent = 0;

        Lease::where('status', Lease::STATUS_ACTIVE)->with(['tenant', 'property'])->chunk(50, function ($leases) use ($reminderDays, $today, &$sent) {
            foreach ($leases as $lease) {
                $dueDate = LeaseDueDateCalculator::nextDueDate($lease, $today);

                if (! $today->isSameDay($dueDate->copy()->subDays($reminderDays))) {
                    continue;
                }

                if ($this->reminderAlreadySent($lease, $dueDate)) {
                    continue;
                }

                $this->notifications->notify(
                    $lease->tenant,
                    Notification::TYPE_PAYMENT_REMINDER,
                    'Rappel de paiement',
                    "Le loyer de {$lease->monthly_rent} FCFA pour {$lease->property->title} est dû le {$dueDate->toDateString()}.",
                );

                $sent++;
            }
        });

        return $sent;
    }

    private function reminderAlreadySent(Lease $lease, Carbon $dueDate): bool
    {
        return Notification::where('user_id', $lease->tenant_id)
            ->where('type', Notification::TYPE_PAYMENT_REMINDER)
            ->where('message', 'like', "%{$dueDate->toDateString()}%")
            ->exists();
    }
}
