<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'          => ['required', 'integer', 'exists:employees,id'],
            'purpose'              => ['required', 'string'],
            'amount'               => ['required', 'numeric', 'min:0'],
            'fund_source'          => ['required', 'string', 'max:100'],
            'ca_date'              => ['nullable', 'date'],
            'project_activity'     => ['nullable', 'string', 'max:255'],
            'budget_id'            => ['nullable', 'integer', 'exists:budgets,id'],
            'date_needed'          => ['nullable', 'date'],
            'due_date_liquidation' => ['nullable', 'date'],
            'remarks'              => ['nullable', 'string'],
        ];
    }
}
