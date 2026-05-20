<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class TransferIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_employee_id' => ['required', 'string', 'exists:employees,uuid'],
            'remarks'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_employee_id.exists' => 'The target employee does not exist.',
        ];
    }
}
