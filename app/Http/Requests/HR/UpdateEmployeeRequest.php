<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_employees');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            // Personal Information
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'gender' => ['sometimes', 'required', Rule::in(['Male', 'Female'])],
            'civil_status' => ['sometimes', 'required', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],

            // Contact Information
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[0-9+()-]+$/'],
            'address' => ['sometimes', 'required', 'string'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'province' => ['sometimes', 'required', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'required', 'string', 'max:10'],

            // Employment Information
            'plantilla_item_no' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('employees', 'plantilla_item_no')->ignore($employeeId)],
            'position' => ['sometimes', 'required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'salary_grade' => ['nullable', 'integer', 'min:1', 'max:33'],
            'step_increment' => ['nullable', 'integer', 'min:1', 'max:8'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'employment_status' => ['sometimes', 'required', Rule::in(['Permanent', 'Temporary', 'Casual', 'Contractual', 'Substitute'])],
            'date_hired' => ['sometimes', 'required', 'date'],
            'date_separated' => ['nullable', 'date', 'after:date_hired'],

            // Government IDs
            'tin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'gsis_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'philhealth_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'pagibig_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'sss_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],

            // Leave Credits
            'vacation_leave_credits' => ['nullable', 'numeric', 'min:0'],
            'sick_leave_credits' => ['nullable', 'numeric', 'min:0'],

            // Status
            'status' => ['sometimes', 'required', Rule::in(['Active', 'Inactive', 'On Leave', 'Retired', 'Resigned'])],
        ];
    }
}
