<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_service_credits');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'credit_type' => ['required', Rule::in([
                'Summer Work',
                'Holiday Work',
                'Overtime',
                'Special Duty',
                'Weekend Work'
            ])],
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'hours_worked' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'credit_type.required' => 'Credit type is required.',
            'credit_type.in' => 'Invalid credit type.',
            'work_date.required' => 'Work date is required.',
            'work_date.before_or_equal' => 'Work date cannot be in the future.',
            'hours_worked.required' => 'Hours worked is required.',
            'hours_worked.min' => 'Hours worked must be at least 0.5 hours.',
            'hours_worked.max' => 'Hours worked cannot exceed 24 hours.',
        ];
    }
}
