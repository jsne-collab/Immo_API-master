<?php

namespace App\Console\Commands;

use App\Services\PaymentReminderService;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:send-reminders';

    /**
     * @var string
     */
    protected $description = 'Envoie un rappel de paiement aux locataires dont l\'échéance approche (règle A.6.7, PAYMENT_REMINDER_DAYS_BEFORE).';

    public function handle(PaymentReminderService $reminderService): int
    {
        $count = $reminderService->sendDueReminders();

        $this->info("{$count} rappel(s) de paiement envoyé(s).");

        return self::SUCCESS;
    }
}
