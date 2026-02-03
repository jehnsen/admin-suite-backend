<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Activity;
use App\Observers\ActivityObserver;
use App\Services\Inventory\AssetTaggingService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register AssetTaggingService as singleton for efficient property number generation
        $this->app->singleton(AssetTaggingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Activity Observer for audit trail metadata injection
        Activity::observe(ActivityObserver::class);
    }
}
