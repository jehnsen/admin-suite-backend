<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionSlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->uuid,
            'ris_number'      => $this->ris_number,
            'division_office' => $this->division_office,
            'purpose'         => $this->purpose,
            'status'          => $this->status,
            'is_pending'      => $this->isPending(),
            'is_approved'     => $this->isApproved(),
            'is_released'     => $this->isReleased(),
            'requested_date'  => $this->requested_date?->format('Y-m-d'),
            'approved_date'   => $this->approved_date?->format('Y-m-d'),
            'released_date'   => $this->released_date?->format('Y-m-d'),
            'remarks'         => $this->remarks,

            'requested_by_employee' => $this->whenLoaded('requestedByEmployee', fn() => [
                'id'        => $this->requestedByEmployee->uuid,
                'full_name' => $this->requestedByEmployee->full_name,
                'position'  => $this->requestedByEmployee->position,
            ]),
            'approved_by_employee' => $this->whenLoaded('approvedByEmployee', fn() => [
                'id'        => $this->approvedByEmployee->uuid,
                'full_name' => $this->approvedByEmployee->full_name,
            ]),
            'released_by_employee' => $this->whenLoaded('releasedByEmployee', fn() => [
                'id'        => $this->releasedByEmployee->uuid,
                'full_name' => $this->releasedByEmployee->full_name,
            ]),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'                 => $item->uuid,
                'stock_number'       => $item->stock_number,
                'description'        => $item->description,
                'unit_of_measure'    => $item->unit_of_measure,
                'quantity_requested' => $item->quantity_requested,
                'quantity_approved'  => $item->quantity_approved,
                'quantity_issued'    => $item->quantity_issued,
                'unit_cost'          => (float) ($item->unit_cost ?? 0),
                'total_cost'         => (float) ($item->total_cost ?? 0),
                'inventory_item'     => $item->inventoryItem ? [
                    'id'        => $item->inventoryItem->uuid,
                    'item_code' => $item->inventoryItem->item_code,
                    'item_name' => $item->inventoryItem->item_name,
                ] : null,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
