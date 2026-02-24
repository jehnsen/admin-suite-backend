<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name'                   => ['sometimes', 'string', 'max:255'],
            'category'                    => ['sometimes', 'string', 'max:100'],
            'unit_of_measure'             => ['sometimes', 'string', 'max:50'],
            'unit_cost'                   => ['sometimes', 'numeric', 'min:0'],
            'quantity'                    => ['sometimes', 'integer', 'min:0'],
            'description'                 => ['sometimes', 'nullable', 'string'],
            'serial_number'               => ['sometimes', 'nullable', 'string', 'max:100'],
            'property_number'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'model'                       => ['sometimes', 'nullable', 'string', 'max:100'],
            'brand'                       => ['sometimes', 'nullable', 'string', 'max:100'],
            'fund_source'                 => ['sometimes', 'nullable', 'string', 'max:100'],
            'supplier'                    => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_acquired'               => ['sometimes', 'nullable', 'date'],
            'po_number'                   => ['sometimes', 'nullable', 'string', 'max:50'],
            'invoice_number'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'condition'                   => ['sometimes', 'nullable', 'string', 'max:50'],
            'status'                      => ['sometimes', 'nullable', 'string', 'max:50'],
            'location'                    => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimated_useful_life_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'depreciation_rate'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'remarks'                     => ['sometimes', 'nullable', 'string'],
        ];
    }
}
