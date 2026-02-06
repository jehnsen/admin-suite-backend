<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class ApplyServiceCreditOffsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('apply_service_credit_offset');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_record_id' => ['required', 'integer', 'exists:attendance_records,id'],
            'credits_to_use' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'attendance_record_id.required' => 'Attendance record is required.',
            'attendance_record_id.exists' => 'Selected attendance record does not exist.',
            'credits_to_use.required' => 'Credits to use is required.',
            'credits_to_use.min' => 'Must use at least 0.5 credits.',
            'credits_to_use.max' => 'Cannot use more than 10 credits at once.',
        ];
    }
}
