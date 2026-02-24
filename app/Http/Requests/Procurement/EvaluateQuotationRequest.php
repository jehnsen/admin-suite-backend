<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluations'                      => 'required|array|min:1',
            'evaluations.*.quotation_id'       => 'required|integer|exists:quotations,id',
            'evaluations.*.ranking'            => 'nullable|integer|min:1',
            'evaluations.*.evaluation_score'   => 'nullable|numeric|min:0|max:100',
            'evaluations.*.total_amount'       => 'nullable|numeric|min:0',
            'evaluations.*.remarks'            => 'nullable|string|max:500',
        ];
    }
}
