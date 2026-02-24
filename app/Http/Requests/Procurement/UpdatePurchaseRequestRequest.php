<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purpose'              => ['sometimes', 'string'],
            'fund_source'          => ['sometimes', 'string', 'max:100'],
            'pr_date'              => ['sometimes', 'nullable', 'date'],
            'department'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'section'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'fund_cluster'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'ppmp_reference'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'procurement_mode'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'estimated_budget'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_amount'         => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'date_needed'          => ['sometimes', 'nullable', 'date'],
            'delivery_date'        => ['sometimes', 'nullable', 'date'],
            'delivery_location'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'remarks'              => ['sometimes', 'nullable', 'string'],
            'terms_and_conditions' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
