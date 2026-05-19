<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->uuid,
            'item_code'                   => $this->item_code,
            'item_name'                   => $this->item_name,
            'description'                 => $this->description,
            'category'                    => $this->category,
            'unit_of_measure'             => $this->unit_of_measure,
            'serial_number'               => $this->serial_number,
            'property_number'             => $this->property_number,
            'model'                       => $this->model,
            'brand'                       => $this->brand,
            'unit_cost'                   => (float) ($this->unit_cost ?? 0),
            'quantity'                    => $this->quantity,
            'total_cost'                  => (float) ($this->total_cost ?? 0),
            'fund_source'                 => $this->fund_source,
            'supplier'                    => $this->supplier,
            'date_acquired'               => $this->date_acquired?->format('Y-m-d'),
            'po_number'                   => $this->po_number,
            'invoice_number'              => $this->invoice_number,
            'condition'                   => $this->condition,
            'status'                      => $this->status,
            'location'                    => $this->location,
            'estimated_useful_life_years' => $this->estimated_useful_life_years,
            'accumulated_depreciation'    => (float) ($this->accumulated_depreciation ?? 0),
            'book_value'                  => (float) ($this->book_value ?? 0),
            'is_serviceable'              => $this->isServiceable(),
            'is_available'                => $this->isAvailable(),
            'is_issued'                   => $this->isIssued(),
            'remarks'                     => $this->remarks,
            // depreciation_rate omitted (internal calculation detail)
            'created_at'                  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                  => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
