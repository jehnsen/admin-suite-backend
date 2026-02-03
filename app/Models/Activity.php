<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $appends = ['module_name', 'action_description'];

    /**
     * Get human-readable module name based on subject type
     */
    public function getModuleNameAttribute(): string
    {
        if (!$this->subject_type) {
            return 'System';
        }

        return match (true) {
            str_contains($this->subject_type, 'Employee'),
            str_contains($this->subject_type, 'LeaveRequest'),
            str_contains($this->subject_type, 'ServiceRecord'),
            str_contains($this->subject_type, 'Training') => 'HR',

            str_contains($this->subject_type, 'PurchaseRequest'),
            str_contains($this->subject_type, 'PurchaseOrder'),
            str_contains($this->subject_type, 'Supplier'),
            str_contains($this->subject_type, 'Quotation'),
            str_contains($this->subject_type, 'Delivery') => 'Procurement',

            str_contains($this->subject_type, 'Liquidation'),
            str_contains($this->subject_type, 'CashAdvance'),
            str_contains($this->subject_type, 'Disbursement'),
            str_contains($this->subject_type, 'Budget') => 'Financial',

            str_contains($this->subject_type, 'Inventory'),
            str_contains($this->subject_type, 'StockCard'),
            str_contains($this->subject_type, 'PhysicalCount') => 'Inventory',

            default => 'System',
        };
    }

    /**
     * Get formatted action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $modelName = $this->subject_type ? class_basename($this->subject_type) : 'Record';
        $event = ucfirst($this->event ?? 'action');

        return "{$event} {$modelName}";
    }

    /**
     * Get causer name (user who performed the action)
     */
    public function getCauserNameAttribute(): ?string
    {
        return $this->causer?->name;
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): array
    {
        $properties = $this->properties ?? collect();

        return [
            'old' => $properties->get('old', []),
            'new' => $properties->get('attributes', []),
        ];
    }
}
