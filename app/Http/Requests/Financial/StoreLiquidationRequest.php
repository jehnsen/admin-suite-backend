<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiquidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_advance_id'        => ['required', 'integer', 'exists:cash_advances,id'],
            'liquidation_date'       => ['required', 'date'],
            'total_expenses'         => ['required', 'numeric', 'min:0'],
            'liquidation_number'     => ['nullable', 'string', 'max:50'],
            'supporting_documents'   => ['nullable', 'string'],
            'summary_of_expenses'    => ['nullable', 'string'],
            'amount_to_refund'       => ['nullable', 'numeric', 'min:0'],
            'additional_cash_needed' => ['nullable', 'numeric', 'min:0'],
            'remarks'                => ['nullable', 'string'],
        ];
    }
}
