<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purpose'              => ['sometimes', 'string'],
            'amount'               => ['sometimes', 'numeric', 'min:0'],
            'fund_source'          => ['sometimes', 'string', 'max:100'],
            'ca_date'              => ['sometimes', 'nullable', 'date'],
            'project_activity'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'budget_id'            => ['sometimes', 'nullable', 'integer', 'exists:budgets,id'],
            'date_needed'          => ['sometimes', 'nullable', 'date'],
            'due_date_liquidation' => ['sometimes', 'nullable', 'date'],
            'remarks'              => ['sometimes', 'nullable', 'string'],
        ];
    }
}
