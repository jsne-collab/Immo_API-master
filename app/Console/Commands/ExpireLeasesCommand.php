<?php

namespace App\Console\Commands;

use App\Services\LeaseService;
use Illuminate\Console\Command;

class ExpireLeasesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'leases:expire';

    /**
     * @var string
     */
    protected $description = 'Passe au statut "expired" les baux actifs dont la date de fin est dépassée (règle A.6.6), et remet le bien/l\'unité concerné à "available".';

    public function handle(LeaseService $leaseService): int
    {
        $count = $leaseService->expireOverdueLeases();

        $this->info("{$count} bail(aux) expiré(s).");

        return self::SUCCESS;
    }
}
