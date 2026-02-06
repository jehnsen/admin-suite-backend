<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
            ]),

            // Credit details
            'credit_type' => $this->credit_type,
            'work_date' => $this->work_date->format('Y-m-d'),
            'description' => $this->description,

            // Credits tracking
            'hours_worked' => (float) $this->hours_worked,
            'credits_earned' => (float) $this->credits_earned,
            'credits_used' => (float) $this->credits_used,
            'credits_balance' => (float) $this->credits_balance,

            // Status
            'status' => $this->status,
            'is_available' => $this->isAvailable(),
            'is_expired' => $this->isExpired(),
            'can_be_approved' => $this->canBeApproved(),

            // Approval info
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'approver' => $this->whenLoaded('approver', fn() => $this->approver ? [
                'id' => $this->approver->id,
                'full_name' => $this->approver->full_name,
            ] : null),

            // Rejection info
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,

            // Expiry
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
