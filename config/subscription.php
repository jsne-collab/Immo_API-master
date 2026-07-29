<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Abonnement plateforme (droits d'utilisation propriétaire)
    |--------------------------------------------------------------------------
    |
    | Montant (FCFA) et périodicité (en mois) que chaque propriétaire doit
    | payer pour utiliser l'app — distinct des loyers (qui vont du locataire
    | au propriétaire). Valeur placeholder en attendant le tarif définitif :
    | c'est le SEUL endroit à modifier pour changer le prix/la fréquence.
    |
    */

    'amount' => env('SUBSCRIPTION_AMOUNT', 5000),

    'period_months' => env('SUBSCRIPTION_PERIOD_MONTHS', 1),
];
