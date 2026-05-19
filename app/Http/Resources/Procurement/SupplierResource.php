<?php

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->uuid,
            'supplier_code'           => $this->supplier_code,
            'business_name'           => $this->business_name,
            'trade_name'              => $this->trade_name,
            'display_name'            => $this->display_name,
            'owner_name'              => $this->owner_name,
            'business_type'           => $this->business_type,
            'email'                   => $this->email,
            'phone_number'            => $this->phone_number,
            'mobile_number'           => $this->mobile_number,
            'address'                 => $this->address,
            'city'                    => $this->city,
            'province'                => $this->province,
            'zip_code'                => $this->zip_code,
            'product_categories'      => $this->product_categories,
            'supplier_classification' => $this->supplier_classification,
            'rating'                  => $this->rating ? (float) $this->rating : null,
            'total_transactions'      => $this->total_transactions,
            'total_amount_transacted' => $this->total_amount_transacted ? (float) $this->total_amount_transacted : null,
            'status'                  => $this->status,
            'is_active'               => $this->isActive(),
            'remarks'                 => $this->remarks,
            // tin, bir_certificate_number, sec_registration, bank_account_* intentionally omitted
            'created_at'              => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'              => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
