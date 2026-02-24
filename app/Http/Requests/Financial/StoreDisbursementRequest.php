<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dv_date'           => ['required', 'date'],
            'payee_name'        => ['required', 'string', 'max:255'],
            'purpose'           => ['required', 'string'],
            'amount'            => ['required', 'numeric', 'min:0'],
            'fund_source'       => ['required', 'string', 'max:100'],
            'payee_address'     => ['nullable', 'string', 'max:500'],
            'payee_tin'         => ['nullable', 'string', 'max:50'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'cash_advance_id'   => ['nullable', 'integer', 'exists:cash_advances,id'],
            'budget_id'         => ['nullable', 'integer', 'exists:budgets,id'],
            'payment_mode'      => ['nullable', 'string', 'max:50'],
            'check_number'      => ['nullable', 'string', 'max:50'],
            'check_date'        => ['nullable', 'date'],
            'bank_name'         => ['nullable', 'string', 'max:100'],
            'gross_amount'      => ['nullable', 'numeric', 'min:0'],
            'tax_withheld'      => ['nullable', 'numeric', 'min:0'],
            'net_amount'        => ['nullable', 'numeric', 'min:0'],
            'remarks'           => ['nullable', 'string'],
        ];
    }
}
