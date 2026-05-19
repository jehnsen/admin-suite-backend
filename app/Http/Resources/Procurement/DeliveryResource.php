<?php

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->uuid,
            'delivery_receipt_number'  => $this->delivery_receipt_number,
            'delivery_date'            => $this->delivery_date?->format('Y-m-d'),
            'delivery_time'            => $this->delivery_time,
            'supplier_dr_number'       => $this->supplier_dr_number,
            'invoice_number'           => $this->invoice_number,
            'invoice_date'             => $this->invoice_date?->format('Y-m-d'),
            'delivered_by_name'        => $this->delivered_by_name,
            'delivered_by_contact'     => $this->delivered_by_contact,
            'received_location'        => $this->received_location,
            'status'                   => $this->status,
            'condition'                => $this->condition,
            'condition_notes'          => $this->condition_notes,
            'inspection_result'        => $this->inspection_result,
            'inspection_remarks'       => $this->inspection_remarks,
            'acceptance_remarks'       => $this->acceptance_remarks,
            'can_be_inspected'         => $this->canBeInspected(),
            'can_be_accepted'          => $this->canBeAccepted(),
            'remarks'                  => $this->remarks,

            'purchase_order' => $this->whenLoaded('purchaseOrder', fn() => [
                'id'        => $this->purchaseOrder->uuid,
                'po_number' => $this->purchaseOrder->po_number,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id'            => $this->supplier->uuid,
                'business_name' => $this->supplier->business_name,
                'display_name'  => $this->supplier->display_name,
            ]),
            'received_by' => $this->whenLoaded('receivedBy', fn() => [
                'id'   => $this->receivedBy->uuid,
                'name' => $this->receivedBy->name,
            ]),
            'received_at'  => $this->received_at?->format('Y-m-d H:i:s'),
            'inspected_by' => $this->whenLoaded('inspectedBy', fn() => [
                'id'   => $this->inspectedBy->uuid,
                'name' => $this->inspectedBy->name,
            ]),
            'inspected_at' => $this->inspected_at?->format('Y-m-d H:i:s'),
            'accepted_by'  => $this->whenLoaded('acceptedBy', fn() => [
                'id'   => $this->acceptedBy->uuid,
                'name' => $this->acceptedBy->name,
            ]),
            'accepted_at'  => $this->accepted_at?->format('Y-m-d H:i:s'),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'                 => $item->uuid,
                'item_description'   => $item->item_description,
                'unit_of_measure'    => $item->unit_of_measure,
                'quantity_ordered'   => $item->quantity_ordered,
                'quantity_delivered' => $item->quantity_delivered,
                'quantity_accepted'  => $item->quantity_accepted,
                'quantity_rejected'  => $item->quantity_rejected,
                'item_condition'     => $item->item_condition,
                'serial_numbers'     => $item->serial_numbers,
                'inspection_notes'   => $item->inspection_notes,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
