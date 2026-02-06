<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\ServiceCredit;
use App\Policies\AttendanceRecordPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\ServiceCreditPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AttendanceRecord::class => AttendanceRecordPolicy::class,
        Document::class => DocumentPolicy::class,
        Employee::class => EmployeePolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
        ServiceCredit::class => ServiceCreditPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
