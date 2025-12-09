<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// HR Module
use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Repositories\HR\EmployeeRepository;
use App\Interfaces\HR\LeaveRequestRepositoryInterface;
use App\Repositories\HR\LeaveRequestRepository;
use App\Interfaces\HR\ServiceRecordRepositoryInterface;
use App\Repositories\HR\ServiceRecordRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // HR Module Repository Bindings
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->bind(ServiceRecordRepositoryInterface::class, ServiceRecordRepository::class);

        // Add more repository bindings here as you create them
        // Inventory Module
        // Finance Module
        // etc.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
