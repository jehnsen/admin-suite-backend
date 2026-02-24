<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class AddLiquidationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date'      => ['required', 'date'],
            'particulars'       => ['required', 'string'],
            'amount'            => ['required', 'numeric', 'min:0'],
            'item_number'       => ['nullable', 'integer'],
            'or_invoice_number' => ['nullable', 'string', 'max:100'],
            'category'          => ['nullable', 'string', 'max:100'],
            'remarks'           => ['nullable', 'string'],
        ];
    }
}
