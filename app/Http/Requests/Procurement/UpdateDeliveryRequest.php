<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_date'           => ['sometimes', 'date'],
            'delivery_receipt_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'supplier_dr_number'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'invoice_number'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'invoice_date'            => ['sometimes', 'nullable', 'date'],
            'delivered_by_name'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'condition'               => ['sometimes', 'nullable', 'string', 'max:50'],
            'condition_notes'         => ['sometimes', 'nullable', 'string'],
            'remarks'                 => ['sometimes', 'nullable', 'string'],
        ];
    }
}
