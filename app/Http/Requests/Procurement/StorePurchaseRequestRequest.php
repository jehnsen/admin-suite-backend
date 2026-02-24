<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purpose'              => ['required', 'string'],
            'fund_source'          => ['required', 'string', 'max:100'],
            'pr_date'              => ['nullable', 'date'],
            'department'           => ['nullable', 'string', 'max:100'],
            'section'              => ['nullable', 'string', 'max:100'],
            'fund_cluster'         => ['nullable', 'string', 'max:100'],
            'ppmp_reference'       => ['nullable', 'string', 'max:100'],
            'procurement_mode'     => ['nullable', 'string', 'max:100'],
            'estimated_budget'     => ['nullable', 'numeric', 'min:0'],
            'total_amount'         => ['nullable', 'numeric', 'min:0'],
            'date_needed'          => ['nullable', 'date'],
            'delivery_date'        => ['nullable', 'date'],
            'delivery_location'    => ['nullable', 'string', 'max:255'],
            'requested_by'         => ['nullable', 'integer', 'exists:employees,id'],
            'remarks'              => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],

            'items'                    => ['nullable', 'array'],
            'items.*.item_description' => ['required_with:items', 'string', 'max:255'],
            'items.*.unit_of_measure'  => ['required_with:items', 'string', 'max:50'],
            'items.*.quantity'         => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price'       => ['required_with:items', 'numeric', 'min:0'],
            'items.*.total_amount'     => ['nullable', 'numeric', 'min:0'],
            'items.*.specifications'   => ['nullable', 'string'],
            'items.*.remarks'          => ['nullable', 'string'],
        ];
    }
}
