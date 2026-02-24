<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fund_source'      => ['required', 'string', 'max:100'],
            'fiscal_year'      => ['required', 'integer', 'min:2000', 'max:2100'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'budget_name'      => ['nullable', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'classification'   => ['nullable', 'string', 'max:50'],
            'quarter'          => ['nullable', 'integer', 'between:1,4'],
            'category'         => ['nullable', 'string', 'max:100'],
            'sub_category'     => ['nullable', 'string', 'max:100'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'managed_by'       => ['nullable', 'integer', 'exists:users,id'],
            'remarks'          => ['nullable', 'string'],
        ];
    }
}
