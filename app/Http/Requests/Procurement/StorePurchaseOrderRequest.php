<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => ['required', 'integer', 'exists:suppliers,id'],
            'total_amount'         => ['required', 'numeric', 'min:0'],
            'purchase_request_id'  => ['nullable', 'integer', 'exists:purchase_requests,id'],
            'quotation_id'         => ['nullable', 'integer', 'exists:quotations,id'],
            'po_date'              => ['nullable', 'date'],
            'subtotal'             => ['nullable', 'numeric', 'min:0'],
            'tax_amount'           => ['nullable', 'numeric', 'min:0'],
            'discount_amount'      => ['nullable', 'numeric', 'min:0'],
            'shipping_cost'        => ['nullable', 'numeric', 'min:0'],
            'fund_source'          => ['nullable', 'string', 'max:100'],
            'fund_cluster'         => ['nullable', 'string', 'max:100'],
            'budget_id'            => ['nullable', 'integer', 'exists:budgets,id'],
            'delivery_location'    => ['nullable', 'string', 'max:255'],
            'delivery_date'        => ['nullable', 'date'],
            'delivery_terms'       => ['nullable', 'string', 'max:255'],
            'payment_terms'        => ['nullable', 'string', 'max:255'],
            'payment_method'       => ['nullable', 'string', 'max:50'],
            'terms_and_conditions' => ['nullable', 'string'],
            'special_instructions' => ['nullable', 'string'],
            'remarks'              => ['nullable', 'string'],

            'items'                    => ['nullable', 'array'],
            'items.*.item_description' => ['required_with:items', 'string', 'max:255'],
            'items.*.unit_of_measure'  => ['required_with:items', 'string', 'max:50'],
            'items.*.quantity'         => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price'       => ['required_with:items', 'numeric', 'min:0'],
            'items.*.total_amount'     => ['nullable', 'numeric', 'min:0'],
            'items.*.specifications'   => ['nullable', 'string'],
            'items.*.remarks'          => ['nullable', 'string'],
        ];
    }
}
