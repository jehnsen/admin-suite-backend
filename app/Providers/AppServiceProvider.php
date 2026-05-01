<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Rate limiter: max 5 login attempts per minute per IP
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'message' => 'Too many login attempts. Please try again in a minute.',
            ], 429)));

        // Rate limiter: max 120 authenticated API requests per minute per user+IP
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(
            (int) env('RATE_LIMIT_PER_MINUTE', 120)
        )
            ->by(($request->user()?->id ?: 'guest') . '|' . $request->ip())
            ->response(fn () => response()->json([
                'message' => 'Too many requests. Please slow down.',
            ], 429)));
    }
}
