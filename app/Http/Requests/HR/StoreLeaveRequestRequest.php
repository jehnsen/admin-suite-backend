<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // All authenticated users can create leave requests
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],

            'leave_type' => ['required', Rule::in([
                'Vacation Leave',
                'Sick Leave',
                'Maternity Leave',
                'Paternity Leave',
                'Special Privilege Leave',
                'Solo Parent Leave',
                'Study Leave',
                'VAWC Leave',
                'Rehabilitation Leave',
                'Special Leave Benefits for Women',
                'Special Emergency Leave',
                'Adoption Leave'
            ])],

            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_requested' => ['nullable', 'numeric', 'min:0.5'],

            // For Sick Leave
            'sick_leave_type' => [
                Rule::requiredIf(fn() => $this->leave_type === 'Sick Leave'),
                'nullable',
                Rule::in(['In Hospital', 'Out Patient'])
            ],
            'illness' => ['nullable', 'string', 'max:500'],

            // For Other Leaves
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'leave_type.required' => 'Leave type is required.',
            'leave_type.in' => 'Invalid leave type selected.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be equal to or after start date.',
            'sick_leave_type.required_if' => 'Sick leave type is required for sick leave requests.',
        ];
    }
}
