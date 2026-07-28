<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Repositories\ActivityLogRepository;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogRepository $logs) {}

    /**
     * Journalise une action (règle A.4 — table `activity_logs`).
     * L'IP est lue depuis la requête HTTP courante, pas besoin de
     * l'injecter dans chaque appelant.
     */
    public function log(?User $user, string $action, ?string $description = null): ActivityLog
    {
        return $this->logs->create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
