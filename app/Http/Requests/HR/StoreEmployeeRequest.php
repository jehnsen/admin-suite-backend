<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_employees');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal Information
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'civil_status' => ['required', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],

            // Contact Information
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+()-]+$/'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],

            // Employment Information
            'employee_number' => ['nullable', 'string', 'max:255', 'unique:employees,employee_number'],
            'plantilla_item_no' => ['required', 'string', 'max:255', 'unique:employees,plantilla_item_no'],
            'position' => ['required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'salary_grade' => ['nullable', 'integer', 'min:1', 'max:33'],
            'step_increment' => ['nullable', 'integer', 'min:1', 'max:8'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'employment_status' => ['required', Rule::in(['Permanent', 'Temporary', 'Casual', 'Contractual', 'Substitute'])],
            'date_hired' => ['required', 'date'],
            'date_separated' => ['nullable', 'date', 'after:date_hired'],

            // Government IDs
            'tin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'gsis_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'philhealth_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'pagibig_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'sss_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],

            // Status
            'status' => ['nullable', Rule::in(['Active', 'Inactive', 'On Leave', 'Retired', 'Resigned'])],

            // User Association
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'plantilla_item_no.required' => 'Plantilla Item Number is required.',
            'plantilla_item_no.unique' => 'This Plantilla Item Number is already assigned.',
            'position.required' => 'Position is required.',
            'employment_status.required' => 'Employment status is required.',
            'date_hired.required' => 'Date hired is required.',
            'mobile_number.regex' => 'Please enter a valid mobile number.',
            'salary_grade.max' => 'Salary grade cannot exceed 33.',
            'step_increment.max' => 'Step increment cannot exceed 8.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'mobile_number' => 'mobile number',
            'plantilla_item_no' => 'Plantilla Item Number',
            'employment_status' => 'employment status',
            'date_hired' => 'date hired',
        ];
    }
}
