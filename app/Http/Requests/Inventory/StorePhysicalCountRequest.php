<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StorePhysicalCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id'    => ['required', 'integer', 'exists:inventory_items,id'],
            'count_date'           => ['required', 'date'],
            'actual_quantity'      => ['required', 'integer', 'min:0'],
            'count_number'         => ['nullable', 'string', 'max:50'],
            'counted_by'           => ['nullable', 'integer', 'exists:users,id'],
            'variance_explanation' => ['nullable', 'string'],
            'corrective_action'    => ['nullable', 'string'],
            'remarks'              => ['nullable', 'string'],
        ];
    }
}
