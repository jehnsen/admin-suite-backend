<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fund_source'      => ['sometimes', 'string', 'max:100'],
            'fiscal_year'      => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'allocated_amount' => ['sometimes', 'numeric', 'min:0'],
            'budget_name'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'      => ['sometimes', 'nullable', 'string'],
            'classification'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'quarter'          => ['sometimes', 'nullable', 'integer', 'between:1,4'],
            'category'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'sub_category'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_date'       => ['sometimes', 'nullable', 'date'],
            'end_date'         => ['sometimes', 'nullable', 'date'],
            'managed_by'       => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'remarks'          => ['sometimes', 'nullable', 'string'],
        ];
    }
}
