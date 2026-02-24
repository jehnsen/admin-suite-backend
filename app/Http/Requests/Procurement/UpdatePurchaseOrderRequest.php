<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_amount'         => ['sometimes', 'numeric', 'min:0'],
            'po_date'              => ['sometimes', 'nullable', 'date'],
            'subtotal'             => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipping_cost'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fund_source'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'fund_cluster'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'budget_id'            => ['sometimes', 'nullable', 'integer', 'exists:budgets,id'],
            'delivery_location'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_date'        => ['sometimes', 'nullable', 'date'],
            'delivery_terms'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_terms'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_method'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'terms_and_conditions' => ['sometimes', 'nullable', 'string'],
            'special_instructions' => ['sometimes', 'nullable', 'string'],
            'remarks'              => ['sometimes', 'nullable', 'string'],
        ];
    }
}
