<?php

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->uuid,
            'quotation_number'    => $this->quotation_number,
            'quotation_date'      => $this->quotation_date?->format('Y-m-d'),
            'validity_date'       => $this->validity_date?->format('Y-m-d'),
            'subtotal'            => (float) ($this->subtotal ?? 0),
            'tax_amount'          => (float) ($this->tax_amount ?? 0),
            'discount_amount'     => (float) ($this->discount_amount ?? 0),
            'shipping_cost'       => (float) ($this->shipping_cost ?? 0),
            'total_amount'        => (float) ($this->total_amount ?? 0),
            'payment_terms'       => $this->payment_terms,
            'delivery_terms'      => $this->delivery_terms,
            'evaluation_score'    => $this->evaluation_score ? (float) $this->evaluation_score : null,
            'evaluation_remarks'  => $this->evaluation_remarks,
            'ranking'             => $this->ranking,
            'is_selected'         => (bool) $this->is_selected,
            'status'              => $this->status,
            'is_valid'            => $this->isValid(),
            'remarks'             => $this->remarks,

            'purchase_request' => $this->whenLoaded('purchaseRequest', fn() => [
                'id'        => $this->purchaseRequest->uuid,
                'pr_number' => $this->purchaseRequest->pr_number,
                'purpose'   => $this->purchaseRequest->purpose,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id'            => $this->supplier->uuid,
                'business_name' => $this->supplier->business_name,
                'display_name'  => $this->supplier->display_name,
            ]),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'               => $item->uuid,
                'item_description' => $item->item_description,
                'unit_of_measure'  => $item->unit_of_measure,
                'quantity'         => $item->quantity,
                'unit_price'       => (float) ($item->unit_price ?? 0),
                'total_price'      => (float) ($item->total_price ?? 0),
                'brand_model'      => $item->brand_model,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
