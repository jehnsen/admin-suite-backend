<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeaveRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Document::class => DocumentPolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
        Employee::class => EmployeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
