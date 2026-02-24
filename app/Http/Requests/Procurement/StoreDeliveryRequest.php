<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id'       => ['required', 'integer', 'exists:purchase_orders,id'],
            'supplier_id'             => ['required', 'integer', 'exists:suppliers,id'],
            'delivery_date'           => ['required', 'date'],
            'delivery_receipt_number' => ['nullable', 'string', 'max:100'],
            'delivery_time'           => ['nullable', 'string', 'max:10'],
            'supplier_dr_number'      => ['nullable', 'string', 'max:100'],
            'invoice_number'          => ['nullable', 'string', 'max:100'],
            'invoice_date'            => ['nullable', 'date'],
            'delivered_by_name'       => ['nullable', 'string', 'max:255'],
            'delivered_by_contact'    => ['nullable', 'string', 'max:50'],
            'vehicle_plate_number'    => ['nullable', 'string', 'max:50'],
            'received_location'       => ['nullable', 'string', 'max:255'],
            'condition'               => ['nullable', 'string', 'max:50'],
            'condition_notes'         => ['nullable', 'string'],
            'remarks'                 => ['nullable', 'string'],

            'items'                              => ['nullable', 'array'],
            'items.*.purchase_order_item_id'     => ['required_with:items', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity_delivered'         => ['required_with:items', 'numeric', 'min:0'],
            'items.*.quantity_accepted'          => ['nullable', 'numeric', 'min:0'],
            'items.*.condition'                  => ['nullable', 'string', 'max:50'],
            'items.*.remarks'                    => ['nullable', 'string'],
        ];
    }
}
