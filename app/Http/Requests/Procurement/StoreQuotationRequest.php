<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_request_id'  => ['required', 'integer', 'exists:purchase_requests,id'],
            'supplier_id'          => ['required', 'integer', 'exists:suppliers,id'],
            'total_amount'         => ['required', 'numeric', 'min:0'],
            'quotation_date'       => ['nullable', 'date'],
            'validity_date'        => ['nullable', 'date'],
            'subtotal'             => ['nullable', 'numeric', 'min:0'],
            'tax_amount'           => ['nullable', 'numeric', 'min:0'],
            'discount_amount'      => ['nullable', 'numeric', 'min:0'],
            'shipping_cost'        => ['nullable', 'numeric', 'min:0'],
            'payment_terms'        => ['nullable', 'string', 'max:255'],
            'delivery_terms'       => ['nullable', 'string', 'max:255'],
            'terms_and_conditions' => ['nullable', 'string'],
            'remarks'              => ['nullable', 'string'],

            'items'                    => ['nullable', 'array'],
            'items.*.item_description' => ['required_with:items', 'string', 'max:255'],
            'items.*.unit_of_measure'  => ['required_with:items', 'string', 'max:50'],
            'items.*.quantity'         => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price'       => ['required_with:items', 'numeric', 'min:0'],
            'items.*.total_amount'     => ['nullable', 'numeric', 'min:0'],
            'items.*.specifications'   => ['nullable', 'string'],
        ];
    }
}
