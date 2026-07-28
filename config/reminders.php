<?php

return [
    // Règle A.6.7 : nombre de jours avant l'échéance à partir duquel un
    // rappel de paiement est envoyé au locataire.
    'payment_reminder_days_before' => env('PAYMENT_REMINDER_DAYS_BEFORE', 5),
];
