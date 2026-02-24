<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dv_date'           => ['sometimes', 'date'],
            'payee_name'        => ['sometimes', 'string', 'max:255'],
            'purpose'           => ['sometimes', 'string'],
            'amount'            => ['sometimes', 'numeric', 'min:0'],
            'fund_source'       => ['sometimes', 'string', 'max:100'],
            'payee_address'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'payee_tin'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'purchase_order_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_orders,id'],
            'budget_id'         => ['sometimes', 'nullable', 'integer', 'exists:budgets,id'],
            'payment_mode'      => ['sometimes', 'nullable', 'string', 'max:50'],
            'check_number'      => ['sometimes', 'nullable', 'string', 'max:50'],
            'check_date'        => ['sometimes', 'nullable', 'date'],
            'bank_name'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'gross_amount'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_withheld'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'net_amount'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'remarks'           => ['sometimes', 'nullable', 'string'],
        ];
    }
}
