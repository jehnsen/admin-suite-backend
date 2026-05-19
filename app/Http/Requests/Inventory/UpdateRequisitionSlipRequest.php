<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequisitionSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'division_office'           => ['nullable', 'string', 'max:255'],
            'purpose'                   => ['sometimes', 'string', 'max:500'],
            'status'                    => ['sometimes', Rule::in(['Draft', 'Pending', 'Approved', 'Released', 'Cancelled'])],
            'requested_date'            => ['sometimes', 'date'],
            'remarks'                   => ['nullable', 'string', 'max:1000'],

            'items'                             => ['sometimes', 'array', 'min:1'],
            'items.*.inventory_item_id'         => ['required_with:items', 'integer', 'exists:inventory_items,id'],
            'items.*.quantity_requested'        => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_of_measure'           => ['required_with:items', 'string', 'max:50'],
            'items.*.unit_cost'                 => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks'                   => ['nullable', 'string', 'max:500'],
        ];
    }
}
