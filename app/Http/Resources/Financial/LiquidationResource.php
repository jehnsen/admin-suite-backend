<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiquidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->uuid,
            'liquidation_number'      => $this->liquidation_number,
            'liquidation_date'        => $this->liquidation_date?->format('Y-m-d'),
            'cash_advance_amount'     => (float) ($this->cash_advance_amount ?? 0),
            'total_expenses'          => (float) ($this->total_expenses ?? 0),
            'amount_to_refund'        => (float) ($this->amount_to_refund ?? 0),
            'additional_cash_needed'  => (float) ($this->additional_cash_needed ?? 0),
            'refund_date'             => $this->refund_date?->format('Y-m-d'),
            'refund_or_number'        => $this->refund_or_number,
            'additional_payment_date' => $this->additional_payment_date?->format('Y-m-d'),
            'status'                  => $this->status,
            'verification_remarks'    => $this->verification_remarks,
            'remarks'                 => $this->remarks,
            // summary_of_expenses and supporting_documents are internal JSON; expose items relation instead

            'cash_advance' => $this->whenLoaded('cashAdvance', fn() => [
                'id'        => $this->cashAdvance->uuid,
                'ca_number' => $this->cashAdvance->ca_number,
                'amount'    => (float) ($this->cashAdvance->amount ?? 0),
            ]),
            'verified_by' => $this->whenLoaded('verifiedBy', fn() => [
                'id'   => $this->verifiedBy->uuid,
                'name' => $this->verifiedBy->name,
            ]),
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id'   => $this->approvedBy->uuid,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'          => $item->uuid,
                'date'        => $item->date?->format('Y-m-d'),
                'description' => $item->description,
                'amount'      => (float) ($item->amount ?? 0),
                'or_number'   => $item->or_number,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
