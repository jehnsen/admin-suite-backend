<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class InspectDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inspection_result'  => 'required|string|in:Passed,Failed',
            'inspection_remarks' => 'nullable|string|max:1000',
        ];
    }
}
