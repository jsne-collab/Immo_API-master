<?php

namespace App\Providers;

use App\Contracts\GoogleIdTokenVerifier;
use App\Events\DashboardCacheShouldFlush;
use App\Listeners\FlushDashboardCache;
use App\Services\Auth\GoogleClientTokenVerifier;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GoogleIdTokenVerifier::class, GoogleClientTokenVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(DashboardCacheShouldFlush::class, FlushDashboardCache::class);
    }
}
