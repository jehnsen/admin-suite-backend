<?php

namespace App\Http\Resources\HR;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'employee_number' => $this->employee->employee_number,
                    'full_name' => $this->employee->full_name,
                    'position' => $this->employee->position,
                ];
            }),

            // Date and time fields
            'attendance_date' => $this->attendance_date->format('Y-m-d'),
            'time_in' => $this->time_in ? Carbon::parse($this->time_in)->format('H:i:s') : null,
            'time_out' => $this->time_out ? Carbon::parse($this->time_out)->format('H:i:s') : null,
            'lunch_out' => $this->lunch_out ? Carbon::parse($this->lunch_out)->format('H:i:s') : null,
            'lunch_in' => $this->lunch_in ? Carbon::parse($this->lunch_in)->format('H:i:s') : null,

            // Status and calculations
            'status' => $this->status,
            'undertime_hours' => (float) $this->undertime_hours,
            'late_minutes' => $this->late_minutes,
            'overtime_hours' => (float) $this->overtime_hours,
            'total_work_hours' => $this->total_work_hours,

            // Metadata
            'remarks' => $this->remarks,
            'import_source' => $this->import_source,

            // Computed fields
            'is_complete' => $this->isComplete(),
            'is_late' => $this->isLate(),
            'has_undertime' => $this->hasUndertime(),
            'can_be_edited' => $this->canBeEdited(),

            // Approval info
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'full_name' => $this->approver->full_name,
                ] : null;
            }),

            // Creator info
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ] : null;
            }),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
