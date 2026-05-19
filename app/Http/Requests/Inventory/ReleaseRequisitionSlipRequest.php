<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseRequisitionSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'released_by_employee_id'   => ['required', 'integer', 'exists:employees,id'],
            'issued_quantities'         => ['required', 'array'],
            'issued_quantities.*'       => ['required', 'integer', 'min:0'],
        ];
    }
}
