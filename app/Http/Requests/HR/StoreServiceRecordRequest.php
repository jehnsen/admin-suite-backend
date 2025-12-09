<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_service_records');
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

            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after:date_from'],

            'designation' => ['required', 'string', 'max:255'],
            'status_of_appointment' => ['required', Rule::in([
                'Permanent',
                'Temporary',
                'Casual',
                'Contractual',
                'Substitute'
            ])],

            'salary_grade' => ['required', 'integer', 'min:1', 'max:33'],
            'step_increment' => ['required', 'integer', 'min:1', 'max:8'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],

            'station_place_of_assignment' => ['required', 'string', 'max:255'],
            'office_entity' => ['required', 'string', 'max:255'],

            'government_service' => ['required', Rule::in(['Yes', 'No'])],

            'action_type' => ['required', Rule::in([
                'New Appointment',
                'Promotion',
                'Transfer',
                'Reclassification',
                'Demotion',
                'Detail',
                'Secondment',
                'Reassignment'
            ])],

            'appointment_authority' => ['nullable', 'string', 'max:255'],
            'appointment_date' => ['nullable', 'date'],

            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'date_from.required' => 'Start date is required.',
            'designation.required' => 'Designation is required.',
            'salary_grade.required' => 'Salary grade is required.',
            'salary_grade.max' => 'Salary grade cannot exceed 33.',
            'step_increment.max' => 'Step increment cannot exceed 8.',
            'action_type.required' => 'Action type is required.',
        ];
    }
}
