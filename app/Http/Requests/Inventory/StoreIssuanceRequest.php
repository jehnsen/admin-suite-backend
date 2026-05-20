<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type'         => ['required', Rule::in(['PAR', 'ICS', 'General'])],
            'inventory_item_id'     => ['required', 'string', 'exists:inventory_items,uuid'],
            'issued_to_employee_id' => ['required', 'string', 'exists:employees,uuid'],
            'issued_by'             => ['required', 'string', 'exists:employees,uuid'],
            'approved_by'           => ['nullable', 'string', 'exists:employees,uuid'],
            'issuance_number'       => ['nullable', 'string', 'max:50', 'unique:issuances,issuance_number'],
            'issued_date'           => ['required', 'date'],
            'expected_return_date'  => ['nullable', 'date', 'after:issued_date'],
            'purpose'               => ['required', Rule::in(['Official Use', 'Personal Accountability', 'Project Use', 'Temporary Assignment', 'Other'])],
            'purpose_details'       => ['nullable', 'string', 'max:1000'],
            'custodianship_type'    => ['sometimes', Rule::in(['Permanent', 'Temporary', 'Shared'])],
            'remarks'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'issued_to_employee_id.exists' => 'The selected employee does not exist.',
            'inventory_item_id.exists'     => 'The selected inventory item does not exist.',
            'expected_return_date.after'   => 'Expected return date must be after the issued date.',
            'issuance_number.unique'       => 'This issuance number is already in use.',
        ];
    }
}
