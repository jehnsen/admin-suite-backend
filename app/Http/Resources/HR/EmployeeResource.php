<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'employee_number' => $this->employee_number,

            // Personal Information
            'full_name' => $this->full_name,
            'full_name_last_first' => $this->full_name_last_first,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth?->age,
            'gender' => $this->gender,
            'civil_status' => $this->civil_status,

            // Contact Information
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,

            // Employment Information
            'plantilla_item_no' => $this->plantilla_item_no,
            'position' => $this->position,
            'position_title' => $this->position_title,
            'salary_grade' => $this->salary_grade,
            'step_increment' => $this->step_increment,
            'monthly_salary' => $this->monthly_salary ? number_format((float)$this->monthly_salary, 2, '.', ',') : null,
            'employment_status' => $this->employment_status,
            'date_hired' => $this->date_hired?->format('Y-m-d'),
            'date_separated' => $this->date_separated?->format('Y-m-d'),
            'years_of_service' => $this->years_of_service,

            // Government IDs
            'tin' => $this->tin,
            'gsis_number' => $this->gsis_number,
            'philhealth_number' => $this->philhealth_number,
            'pagibig_number' => $this->pagibig_number,
            'sss_number' => $this->sss_number,

            // Leave Credits
            'vacation_leave_credits' => (float) $this->vacation_leave_credits,
            'sick_leave_credits' => (float) $this->sick_leave_credits,

            // Status
            'status' => $this->status,
            'is_active' => $this->isActive(),

            // Relationships
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
