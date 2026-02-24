<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class PromoteEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_position'          => 'required|string|max:255',
            'new_salary_grade'      => 'required|integer|min:1|max:33',
            'new_step_increment'    => 'nullable|integer|min:1|max:8',
            'new_monthly_salary'    => 'required|numeric|min:0',
            'effective_date'        => 'required|date',
            'station'               => 'nullable|string|max:255',
            'office_entity'         => 'nullable|string|max:255',
            'appointment_authority' => 'nullable|string|max:255',
            'appointment_date'      => 'nullable|date',
            'remarks'               => 'nullable|string|max:1000',
        ];
    }
}
