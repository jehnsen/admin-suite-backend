<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRequisitionSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approved_by_employee_id'           => ['required', 'integer', 'exists:employees,id'],
            'approved_quantities'               => ['required', 'array'],
            'approved_quantities.*'             => ['required', 'integer', 'min:0'],
        ];
    }
}
