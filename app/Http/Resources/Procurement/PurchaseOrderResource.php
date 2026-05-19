<?php

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->uuid,
            'po_number'             => $this->po_number,
            'po_date'               => $this->po_date?->format('Y-m-d'),
            'subtotal'              => (float) ($this->subtotal ?? 0),
            'tax_amount'            => (float) ($this->tax_amount ?? 0),
            'discount_amount'       => (float) ($this->discount_amount ?? 0),
            'shipping_cost'         => (float) ($this->shipping_cost ?? 0),
            'total_amount'          => (float) ($this->total_amount ?? 0),
            'fund_source'           => $this->fund_source,
            'fund_cluster'          => $this->fund_cluster,
            'delivery_location'     => $this->delivery_location,
            'delivery_date'         => $this->delivery_date?->format('Y-m-d'),
            'delivery_terms'        => $this->delivery_terms,
            'payment_terms'         => $this->payment_terms,
            'payment_method'        => $this->payment_method,
            'special_instructions'  => $this->special_instructions,
            'status'                => $this->status,
            'can_be_approved'       => $this->canBeApproved(),
            'is_fully_delivered'    => $this->isFullyDelivered(),
            'remarks'               => $this->remarks,

            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id'            => $this->supplier->uuid,
                'business_name' => $this->supplier->business_name,
                'display_name'  => $this->supplier->display_name,
                'email'         => $this->supplier->email,
                'phone_number'  => $this->supplier->phone_number,
            ]),
            'purchase_request' => $this->whenLoaded('purchaseRequest', fn() => [
                'id'        => $this->purchaseRequest->uuid,
                'pr_number' => $this->purchaseRequest->pr_number,
                'purpose'   => $this->purchaseRequest->purpose,
            ]),
            'budget' => $this->whenLoaded('budget', fn() => [
                'id'          => $this->budget->uuid,
                'budget_code' => $this->budget->budget_code,
                'budget_name' => $this->budget->budget_name,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id'   => $this->approvedBy->uuid,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'prepared_by' => $this->whenLoaded('preparedBy', fn() => [
                'id'   => $this->preparedBy->uuid,
                'name' => $this->preparedBy->name,
            ]),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'               => $item->uuid,
                'item_number'      => $item->item_number,
                'item_code'        => $item->item_code,
                'item_description' => $item->item_description,
                'brand_model'      => $item->brand_model,
                'unit_of_measure'  => $item->unit_of_measure,
                'quantity_ordered' => $item->quantity_ordered,
                'quantity_delivered' => $item->quantity_delivered,
                'quantity_remaining' => $item->quantity_remaining,
                'unit_price'       => (float) ($item->unit_price ?? 0),
                'total_price'      => (float) ($item->total_price ?? 0),
                'delivery_status'  => $item->delivery_status,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
