<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequisitionSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_by_employee_id'  => ['required', 'integer', 'exists:employees,id'],
            'ris_number'                => ['nullable', 'string', 'max:50', 'unique:requisition_slips,ris_number'],
            'division_office'           => ['nullable', 'string', 'max:255'],
            'purpose'                   => ['required', 'string', 'max:500'],
            'status'                    => ['sometimes', Rule::in(['Draft', 'Pending'])],
            'requested_date'            => ['required', 'date'],
            'remarks'                   => ['nullable', 'string', 'max:1000'],

            'items'                             => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id'         => ['required', 'integer', 'exists:inventory_items,id'],
            'items.*.quantity_requested'        => ['required', 'integer', 'min:1'],
            'items.*.unit_of_measure'           => ['required', 'string', 'max:50'],
            'items.*.unit_cost'                 => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks'                   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                        => 'At least one item is required.',
            'items.*.inventory_item_id.exists'      => 'One or more selected items do not exist.',
            'items.*.quantity_requested.min'        => 'Quantity requested must be at least 1.',
        ];
    }
}
