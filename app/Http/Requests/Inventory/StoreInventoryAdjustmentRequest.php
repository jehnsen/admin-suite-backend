<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id'   => ['required', 'integer', 'exists:inventory_items,id'],
            'adjustment_type'     => ['required', 'string', 'max:50'],
            'quantity_adjusted'   => ['required', 'numeric'],
            'reason'              => ['required', 'string'],
            'adjustment_date'     => ['nullable', 'date'],
            'supporting_document' => ['nullable', 'string', 'max:500'],
            'remarks'             => ['nullable', 'string'],
        ];
    }
}
