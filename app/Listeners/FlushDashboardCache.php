<?php

namespace App\Listeners;

use App\Events\DashboardCacheShouldFlush;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;

class FlushDashboardCache
{
    public function handle(DashboardCacheShouldFlush $event): void
    {
        Cache::forget(DashboardService::ownerCacheKey($event->ownerId));
        Cache::forget(DashboardService::revenueCacheKey($event->ownerId));
    }
}
