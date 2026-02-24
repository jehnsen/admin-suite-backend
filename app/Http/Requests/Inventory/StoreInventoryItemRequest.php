<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name'                   => ['required', 'string', 'max:255'],
            'category'                    => ['required', 'string', 'max:100'],
            'unit_of_measure'             => ['required', 'string', 'max:50'],
            'unit_cost'                   => ['required', 'numeric', 'min:0'],
            'quantity'                    => ['required', 'integer', 'min:0'],
            'item_code'                   => ['nullable', 'string', 'max:50'],
            'description'                 => ['nullable', 'string'],
            'serial_number'               => ['nullable', 'string', 'max:100'],
            'property_number'             => ['nullable', 'string', 'max:100'],
            'model'                       => ['nullable', 'string', 'max:100'],
            'brand'                       => ['nullable', 'string', 'max:100'],
            'fund_source'                 => ['nullable', 'string', 'max:100'],
            'supplier'                    => ['nullable', 'string', 'max:255'],
            'date_acquired'               => ['nullable', 'date'],
            'po_number'                   => ['nullable', 'string', 'max:50'],
            'invoice_number'              => ['nullable', 'string', 'max:100'],
            'condition'                   => ['nullable', 'string', 'max:50'],
            'location'                    => ['nullable', 'string', 'max:255'],
            'estimated_useful_life_years' => ['nullable', 'integer', 'min:0'],
            'depreciation_rate'           => ['nullable', 'numeric', 'min:0'],
            'delivery_item_id'            => ['nullable', 'integer'],
            'remarks'                     => ['nullable', 'string'],
        ];
    }
}
