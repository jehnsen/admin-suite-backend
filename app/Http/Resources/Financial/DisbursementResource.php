<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisbursementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->uuid,
            'dv_number'    => $this->dv_number,
            'dv_date'      => $this->dv_date?->format('Y-m-d'),
            'payee_name'   => $this->payee_name,
            'purpose'      => $this->purpose,
            'fund_source'  => $this->fund_source,
            'payment_mode' => $this->payment_mode,
            'check_number' => $this->check_number,
            'check_date'   => $this->check_date?->format('Y-m-d'),
            'amount'       => (float) ($this->amount ?? 0),
            'gross_amount' => (float) ($this->gross_amount ?? 0),
            'net_amount'   => (float) ($this->net_amount ?? 0),
            'status'       => $this->status,
            'remarks'      => $this->remarks,
            // payee_tin, tax_withheld, payee_address, bank_name intentionally omitted

            'purchase_order' => $this->whenLoaded('purchaseOrder', fn() => [
                'id'        => $this->purchaseOrder->uuid,
                'po_number' => $this->purchaseOrder->po_number,
            ]),
            'budget' => $this->whenLoaded('budget', fn() => [
                'id'          => $this->budget->uuid,
                'budget_code' => $this->budget->budget_code,
            ]),
            'certified_by' => $this->whenLoaded('certifiedBy', fn() => [
                'id'   => $this->certifiedBy->uuid,
                'name' => $this->certifiedBy->name,
            ]),
            'certified_at' => $this->certified_at?->format('Y-m-d H:i:s'),
            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id'   => $this->approvedBy->uuid,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'paid_by' => $this->whenLoaded('paidBy', fn() => [
                'id'   => $this->paidBy->uuid,
                'name' => $this->paidBy->name,
            ]),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
