<?php

namespace App\Repositories;

use App\Models\ActivityLog;

class ActivityLogRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }
}
