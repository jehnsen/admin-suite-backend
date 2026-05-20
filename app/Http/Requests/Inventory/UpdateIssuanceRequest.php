<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issued_to_employee_id' => ['sometimes', 'string', 'exists:employees,uuid'],
            'approved_by'           => ['nullable', 'string', 'exists:employees,uuid'],
            'expected_return_date'  => ['nullable', 'date'],
            'purpose'               => ['sometimes', Rule::in(['Official Use', 'Personal Accountability', 'Project Use', 'Temporary Assignment', 'Other'])],
            'purpose_details'       => ['nullable', 'string', 'max:1000'],
            'custodianship_type'    => ['sometimes', Rule::in(['Permanent', 'Temporary', 'Shared'])],
            'status'                => ['sometimes', Rule::in(['Active', 'Returned', 'Transferred', 'Lost', 'Damaged'])],
            'remarks'               => ['nullable', 'string', 'max:1000'],
        ];
    }
}
