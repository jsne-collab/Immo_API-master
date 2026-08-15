<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Abonnement plateforme (droits d'utilisation propriétaire)
    |--------------------------------------------------------------------------
    |
    | Montant (FCFA) et périodicité que chaque propriétaire doit payer pour
    | utiliser l'app — distinct des loyers (qui vont du locataire au
    | propriétaire). Deux formules au choix du propriétaire (audit du
    | 13/08/2026) : c'est le SEUL endroit à modifier pour changer les tarifs.
    |
    */

    'plans' => [
        'monthly' => [
            'label' => 'Mensuel',
            'amount' => env('SUBSCRIPTION_MONTHLY_AMOUNT', 10000),
            'period_months' => 1,
        ],
        'yearly' => [
            'label' => 'Annuel',
            'amount' => env('SUBSCRIPTION_YEARLY_AMOUNT', 50000),
            'period_months' => 12,
        ],
    ],

    'default_plan' => 'monthly',
];
