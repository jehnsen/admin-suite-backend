<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Employee Information
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
                'position' => $this->employee->position,
            ]),

            // Leave Details
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days_requested' => (float) $this->days_requested,

            // Sick Leave Specific
            'sick_leave_type' => $this->sick_leave_type,
            'illness' => $this->illness,

            // Reason
            'reason' => $this->reason,

            // Status
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'can_be_cancelled' => $this->canBeCancelled(),

            // Workflow
            'recommender' => $this->whenLoaded('recommender', fn() => [
                'id' => $this->recommender?->id,
                'full_name' => $this->recommender?->full_name,
            ]),
            'recommended_at' => $this->recommended_at?->format('Y-m-d H:i:s'),
            'recommendation_remarks' => $this->recommendation_remarks,

            'approver' => $this->whenLoaded('approver', fn() => [
                'id' => $this->approver?->id,
                'full_name' => $this->approver?->full_name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'approval_remarks' => $this->approval_remarks,

            'disapprover' => $this->whenLoaded('disapprover', fn() => [
                'id' => $this->disapprover?->id,
                'full_name' => $this->disapprover?->full_name,
            ]),
            'disapproved_at' => $this->disapproved_at?->format('Y-m-d H:i:s'),
            'disapproval_reason' => $this->disapproval_reason,

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
