<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'type'             => ['required', 'in:Income,Expense,Transfer,Adjustment'],
            'category'         => ['required', 'string'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'description'      => ['required', 'string'],
            'fund_source'      => ['nullable', 'string'],
            'payment_method'   => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'payer'            => ['nullable', 'string'],
            'payee'            => ['nullable', 'string'],
            'budget_id'        => ['nullable', 'exists:budgets,id'],
            'employee_id'      => ['nullable', 'exists:employees,id'],
        ];
    }
}
