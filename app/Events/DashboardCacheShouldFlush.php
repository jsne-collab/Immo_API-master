<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Levé chaque fois qu'un événement (paiement, bail...) rend les stats du
 * tableau de bord d'un propriétaire potentiellement obsolètes.
 */
class DashboardCacheShouldFlush
{
    use Dispatchable;

    public function __construct(public readonly int $ownerId) {}
}
