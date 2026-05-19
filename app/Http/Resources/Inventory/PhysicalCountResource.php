<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhysicalCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->uuid,
            'count_number'         => $this->count_number,
            'count_date'           => $this->count_date?->format('Y-m-d'),
            'system_quantity'      => $this->system_quantity,
            'actual_quantity'      => $this->actual_quantity,
            'variance'             => $this->variance,
            'variance_type'        => $this->variance_type,
            'status'               => $this->status,
            'has_variance'         => $this->hasVariance(),
            'variance_explanation' => $this->variance_explanation,
            'corrective_action'    => $this->corrective_action,
            'remarks'              => $this->remarks,

            'inventory_item' => $this->whenLoaded('inventoryItem', fn() => [
                'id'        => $this->inventoryItem->uuid,
                'item_code' => $this->inventoryItem->item_code,
                'item_name' => $this->inventoryItem->item_name,
                'category'  => $this->inventoryItem->category,
            ]),
            'counted_by' => $this->whenLoaded('countedBy', fn() => [
                'id'   => $this->countedBy->uuid,
                'name' => $this->countedBy->name,
            ]),
            'verified_by' => $this->whenLoaded('verifiedBy', fn() => [
                'id'   => $this->verifiedBy->uuid,
                'name' => $this->verifiedBy->name,
            ]),
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
