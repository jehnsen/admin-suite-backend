<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name'           => ['sometimes', 'string', 'max:255'],
            'email'                   => ['sometimes', 'email', 'max:255'],
            'phone_number'            => ['sometimes', 'string', 'max:50'],
            'trade_name'              => ['sometimes', 'nullable', 'string', 'max:255'],
            'owner_name'              => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_type'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'mobile_number'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'address'                 => ['sometimes', 'nullable', 'string', 'max:500'],
            'city'                    => ['sometimes', 'nullable', 'string', 'max:100'],
            'province'                => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code'                => ['sometimes', 'nullable', 'string', 'max:10'],
            'tin'                     => ['sometimes', 'nullable', 'string', 'max:50'],
            'bir_certificate_number'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'dti_registration'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'sec_registration'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'mayors_permit'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'philgeps_registration'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_name'               => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_number'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_name'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_categories'      => ['sometimes', 'nullable', 'array'],
            'supplier_classification' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'                  => ['sometimes', 'nullable', 'string', 'max:50'],
            'remarks'                 => ['sometimes', 'nullable', 'string'],
        ];
    }
}
