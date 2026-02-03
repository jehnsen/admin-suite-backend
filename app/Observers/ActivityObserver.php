<?php

namespace App\Observers;

use App\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the Activity "creating" event.
     */
    public function creating(Activity $activity): void
    {
        // Attach IP address from service container (set by middleware)
        if (app()->bound('activitylog.ip')) {
            $activity->ip_address = app('activitylog.ip');
        }

        // Attach user agent from service container (set by middleware)
        if (app()->bound('activitylog.user_agent')) {
            $activity->user_agent = app('activitylog.user_agent');
        }

        // Auto-determine module based on subject type
        if ($activity->subject_type) {
            $activity->module = $this->getModule($activity->subject_type);
        }
    }

    /**
     * Determine module from subject type
     */
    private function getModule(string $subjectType): string
    {
        return match (true) {
            str_contains($subjectType, 'Employee'),
            str_contains($subjectType, 'LeaveRequest'),
            str_contains($subjectType, 'ServiceRecord'),
            str_contains($subjectType, 'Training') => 'HR',

            str_contains($subjectType, 'PurchaseRequest'),
            str_contains($subjectType, 'PurchaseOrder'),
            str_contains($subjectType, 'Supplier'),
            str_contains($subjectType, 'Quotation'),
            str_contains($subjectType, 'Delivery') => 'Procurement',

            str_contains($subjectType, 'Liquidation'),
            str_contains($subjectType, 'CashAdvance'),
            str_contains($subjectType, 'Disbursement'),
            str_contains($subjectType, 'Budget'),
            str_contains($subjectType, 'Transaction') => 'Financial',

            str_contains($subjectType, 'Inventory'),
            str_contains($subjectType, 'StockCard'),
            str_contains($subjectType, 'PhysicalCount') => 'Inventory',

            str_contains($subjectType, 'User') => 'System',

            default => 'Other',
        };
    }
}
