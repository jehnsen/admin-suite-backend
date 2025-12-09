<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRecordResource extends JsonResource
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

            // Employee
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
            ]),

            // Service Period
            'date_from' => $this->date_from?->format('Y-m-d'),
            'date_to' => $this->date_to?->format('Y-m-d'),
            'is_current' => $this->isCurrent(),
            'duration_months' => $this->duration,

            // Position Details
            'designation' => $this->designation,
            'status_of_appointment' => $this->status_of_appointment,

            // Compensation
            'salary_grade' => $this->salary_grade,
            'step_increment' => $this->step_increment,
            'monthly_salary' => $this->monthly_salary ? number_format((float)$this->monthly_salary, 2, '.', ',') : null,

            // Assignment
            'station_place_of_assignment' => $this->station_place_of_assignment,
            'office_entity' => $this->office_entity,

            // Government Service
            'government_service' => $this->government_service,

            // Action
            'action_type' => $this->action_type,
            'appointment_authority' => $this->appointment_authority,
            'appointment_date' => $this->appointment_date?->format('Y-m-d'),

            // Additional Info
            'remarks' => $this->remarks,

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
