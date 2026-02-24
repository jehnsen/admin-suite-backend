<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name'           => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'email', 'max:255'],
            'phone_number'            => ['required', 'string', 'max:50'],
            'trade_name'              => ['nullable', 'string', 'max:255'],
            'owner_name'              => ['nullable', 'string', 'max:255'],
            'business_type'           => ['nullable', 'string', 'max:100'],
            'mobile_number'           => ['nullable', 'string', 'max:50'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'province'                => ['nullable', 'string', 'max:100'],
            'zip_code'                => ['nullable', 'string', 'max:10'],
            'tin'                     => ['nullable', 'string', 'max:50'],
            'bir_certificate_number'  => ['nullable', 'string', 'max:100'],
            'dti_registration'        => ['nullable', 'string', 'max:100'],
            'sec_registration'        => ['nullable', 'string', 'max:100'],
            'mayors_permit'           => ['nullable', 'string', 'max:100'],
            'philgeps_registration'   => ['nullable', 'string', 'max:100'],
            'bank_name'               => ['nullable', 'string', 'max:100'],
            'bank_account_number'     => ['nullable', 'string', 'max:100'],
            'bank_account_name'       => ['nullable', 'string', 'max:255'],
            'product_categories'      => ['nullable', 'array'],
            'supplier_classification' => ['nullable', 'string', 'max:100'],
            'remarks'                 => ['nullable', 'string'],
        ];
    }
}
