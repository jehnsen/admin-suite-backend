<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class TagDeliveryAssetsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_details' => 'required|array|min:1',
            'asset_details.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'asset_details.*.serial_number' => 'nullable|string|max:255',
            'asset_details.*.property_number' => [
                'nullable',
                'string',
                'regex:/^PROP-\d{4}-\d{4}$/',
                'unique:inventory_items,property_number',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asset_details.required' => 'Asset details are required.',
            'asset_details.*.inventory_item_id.required' => 'Inventory item ID is required for each asset.',
            'asset_details.*.inventory_item_id.exists' => 'The inventory item does not exist.',
            'asset_details.*.property_number.regex' => 'Property number must match format PROP-YYYY-XXXX.',
            'asset_details.*.property_number.unique' => 'This property number is already in use.',
        ];
    }
}
