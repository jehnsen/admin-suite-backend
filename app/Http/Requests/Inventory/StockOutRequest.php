<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id'  => ['required', 'integer', 'exists:inventory_items,id'],
            'transaction_date'   => ['required', 'date'],
            'quantity_out'       => ['required', 'integer', 'min:1'],
            'reference_number'   => ['nullable', 'string', 'max:100'],
            'source_destination' => ['nullable', 'string', 'max:255'],
            'remarks'            => ['nullable', 'string'],
        ];
    }
}
