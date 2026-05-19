<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->uuid,
            'adjustment_number'   => $this->adjustment_number,
            'adjustment_date'     => $this->adjustment_date?->format('Y-m-d'),
            'adjustment_type'     => $this->adjustment_type,
            'quantity_before'     => $this->quantity_before,
            'quantity_adjusted'   => $this->quantity_adjusted,
            'quantity_after'      => $this->quantity_after,
            'reason'              => $this->reason,
            'supporting_document' => $this->supporting_document,
            'status'              => $this->status,
            'can_be_approved'     => $this->canBeApproved(),
            'is_approved'         => $this->isApproved(),
            'remarks'             => $this->remarks,

            'inventory_item' => $this->whenLoaded('inventoryItem', fn() => [
                'id'        => $this->inventoryItem->uuid,
                'item_code' => $this->inventoryItem->item_code,
                'item_name' => $this->inventoryItem->item_name,
            ]),
            'prepared_by' => $this->whenLoaded('preparedBy', fn() => [
                'id'   => $this->preparedBy->uuid,
                'name' => $this->preparedBy->name,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id'   => $this->approvedBy->uuid,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
