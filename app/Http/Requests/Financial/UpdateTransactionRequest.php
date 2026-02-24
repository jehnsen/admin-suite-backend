<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['sometimes', 'date'],
            'type'             => ['sometimes', 'in:Income,Expense,Transfer,Adjustment'],
            'category'         => ['sometimes', 'string'],
            'amount'           => ['sometimes', 'numeric', 'min:0'],
            'description'      => ['sometimes', 'string'],
            'fund_source'      => ['sometimes', 'nullable', 'string'],
            'payment_method'   => ['sometimes', 'nullable', 'string'],
            'reference_number' => ['sometimes', 'nullable', 'string'],
            'payer'            => ['sometimes', 'nullable', 'string'],
            'payee'            => ['sometimes', 'nullable', 'string'],
            'budget_id'        => ['sometimes', 'nullable', 'exists:budgets,id'],
            'employee_id'      => ['sometimes', 'nullable', 'exists:employees,id'],
            'remarks'          => ['sometimes', 'nullable', 'string'],
        ];
    }
}
