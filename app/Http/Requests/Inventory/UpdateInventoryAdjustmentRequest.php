<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type'     => ['sometimes', 'string', 'max:50'],
            'quantity_adjusted'   => ['sometimes', 'numeric'],
            'reason'              => ['sometimes', 'string'],
            'adjustment_date'     => ['sometimes', 'nullable', 'date'],
            'supporting_document' => ['sometimes', 'nullable', 'string', 'max:500'],
            'remarks'             => ['sometimes', 'nullable', 'string'],
        ];
    }
}
