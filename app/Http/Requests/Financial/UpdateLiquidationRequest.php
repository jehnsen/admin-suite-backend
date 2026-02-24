<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLiquidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'liquidation_date'       => ['sometimes', 'date'],
            'total_expenses'         => ['sometimes', 'numeric', 'min:0'],
            'supporting_documents'   => ['sometimes', 'nullable', 'string'],
            'summary_of_expenses'    => ['sometimes', 'nullable', 'string'],
            'amount_to_refund'       => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'additional_cash_needed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'remarks'                => ['sometimes', 'nullable', 'string'],
        ];
    }
}
