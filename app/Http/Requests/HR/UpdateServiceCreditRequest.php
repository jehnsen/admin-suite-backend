<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_service_credits');
    }

    public function rules(): array
    {
        return [
            'credit_type' => ['sometimes', Rule::in([
                'Summer Work',
                'Holiday Work',
                'Overtime',
                'Special Duty',
                'Weekend Work'
            ])],
            'work_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'hours_worked' => ['sometimes', 'numeric', 'min:0.5', 'max:24'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_type.in' => 'Invalid credit type.',
            'work_date.before_or_equal' => 'Work date cannot be in the future.',
            'hours_worked.min' => 'Hours worked must be at least 0.5 hours.',
            'hours_worked.max' => 'Hours worked cannot exceed 24 hours.',
        ];
    }
}
