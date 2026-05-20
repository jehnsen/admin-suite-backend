<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Shared fields applied to every issuance in the batch
            'issued_to_employee_id' => ['required', 'string', 'exists:employees,uuid'],
            'issued_by'             => ['required', 'string', 'exists:employees,uuid'],
            'approved_by'           => ['nullable', 'string', 'exists:employees,uuid'],
            'issued_date'           => ['required', 'date'],
            'purpose'               => ['required', Rule::in([
                'Official Use',
                'Personal Accountability',
                'Project Use',
                'Temporary Assignment',
                'Other',
            ])],
            'purpose_details'       => ['nullable', 'string', 'max:1000'],
            'custodianship_type'    => ['sometimes', Rule::in(['Permanent', 'Temporary', 'Shared'])],
            'expected_return_date'  => ['nullable', 'date', 'after:issued_date'],
            'remarks'               => ['nullable', 'string', 'max:1000'],

            // Per-item overrides
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.document_type'         => ['required', Rule::in(['PAR', 'ICS', 'General'])],
            'items.*.inventory_item_id'     => ['required', 'string', 'exists:inventory_items,uuid'],
            'items.*.purpose'               => ['sometimes', Rule::in([
                'Official Use',
                'Personal Accountability',
                'Project Use',
                'Temporary Assignment',
                'Other',
            ])],
            'items.*.remarks'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                        => 'At least one item is required.',
            'items.*.document_type.required'        => 'Each item must specify a document type (PAR, ICS, or General).',
            'items.*.inventory_item_id.required'    => 'Each item must specify an inventory item.',
            'items.*.inventory_item_id.exists'      => 'One or more inventory items do not exist.',
        ];
    }
}
