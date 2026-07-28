<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Règle A.6.6 : expire automatiquement les baux dont la date de fin est dépassée.
Schedule::command('leases:expire')->daily();

// Règle A.6.7 : rappels de paiement automatiques avant échéance.
Schedule::command('payments:send-reminders')->daily();
