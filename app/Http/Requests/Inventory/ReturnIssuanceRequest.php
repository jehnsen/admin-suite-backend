<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_return_date'  => ['required', 'date', 'before_or_equal:today'],
            'condition_on_return' => ['required', Rule::in(['Good', 'Fair', 'Poor', 'Damaged', 'Not Applicable'])],
            'return_remarks'      => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_return_date.before_or_equal' => 'Return date cannot be in the future.',
        ];
    }
}
