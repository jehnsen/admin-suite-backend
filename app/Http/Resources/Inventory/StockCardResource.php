<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->uuid,
            'transaction_date'   => $this->transaction_date?->format('Y-m-d'),
            'reference_number'   => $this->reference_number,
            'transaction_type'   => $this->transaction_type,
            'source_destination' => $this->source_destination,
            'quantity_in'        => $this->quantity_in,
            'quantity_out'       => $this->quantity_out,
            'balance'            => $this->balance,
            'unit_cost'          => (float) ($this->unit_cost ?? 0),
            'total_cost'         => (float) ($this->total_cost ?? 0),
            'remarks'            => $this->remarks,

            'inventory_item' => $this->whenLoaded('inventoryItem', fn() => [
                'id'        => $this->inventoryItem->uuid,
                'item_code' => $this->inventoryItem->item_code,
                'item_name' => $this->inventoryItem->item_name,
            ]),
            'processed_by' => $this->whenLoaded('processedBy', fn() => [
                'id'   => $this->processedBy->uuid,
                'name' => $this->processedBy->name,
            ]),
            // Raw delivery_id, issuance_id, purchase_order_id omitted; expose via relationships if loaded
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
