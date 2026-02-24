<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_amount'         => ['sometimes', 'numeric', 'min:0'],
            'quotation_date'       => ['sometimes', 'nullable', 'date'],
            'validity_date'        => ['sometimes', 'nullable', 'date'],
            'subtotal'             => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipping_cost'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'payment_terms'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_terms'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'terms_and_conditions' => ['sometimes', 'nullable', 'string'],
            'remarks'              => ['sometimes', 'nullable', 'string'],
        ];
    }
}
