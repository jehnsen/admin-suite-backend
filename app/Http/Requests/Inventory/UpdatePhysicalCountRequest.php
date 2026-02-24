<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhysicalCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count_date'           => ['sometimes', 'date'],
            'actual_quantity'      => ['sometimes', 'integer', 'min:0'],
            'counted_by'           => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'variance_explanation' => ['sometimes', 'nullable', 'string'],
            'corrective_action'    => ['sometimes', 'nullable', 'string'],
            'remarks'              => ['sometimes', 'nullable', 'string'],
        ];
    }
}
