<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssuanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->uuid,
            'issuance_number'       => $this->issuance_number,
            'document_type'         => $this->document_type,
            'issued_date'           => $this->issued_date?->format('Y-m-d'),
            'expected_return_date'  => $this->expected_return_date?->format('Y-m-d'),
            'actual_return_date'    => $this->actual_return_date?->format('Y-m-d'),
            'purpose'               => $this->purpose,
            'purpose_details'       => $this->purpose_details,
            'custodianship_type'    => $this->custodianship_type,
            'status'                => $this->status,
            'condition_on_return'   => $this->condition_on_return,
            'return_remarks'        => $this->return_remarks,
            'acknowledged_at'       => $this->acknowledged_at?->format('Y-m-d H:i:s'),
            'is_active'             => $this->isActive(),
            'is_overdue'            => $this->isOverdue(),
            'is_acknowledged'       => $this->isAcknowledged(),
            'remarks'               => $this->remarks,
            // acknowledgement_signature_path intentionally omitted

            'inventory_item' => $this->whenLoaded('inventoryItem', fn() => [
                'id'              => $this->inventoryItem->uuid,
                'item_code'       => $this->inventoryItem->item_code,
                'item_name'       => $this->inventoryItem->item_name,
                'category'        => $this->inventoryItem->category,
                'serial_number'   => $this->inventoryItem->serial_number,
                'property_number' => $this->inventoryItem->property_number,
            ]),
            'issued_to_employee' => $this->whenLoaded('issuedToEmployee', fn() => [
                'id'        => $this->issuedToEmployee->uuid,
                'full_name' => $this->issuedToEmployee->full_name,
                'position'  => $this->issuedToEmployee->position,
            ]),
            'issued_by_employee' => $this->whenLoaded('issuedByEmployee', fn() => [
                'id'        => $this->issuedByEmployee->uuid,
                'full_name' => $this->issuedByEmployee->full_name,
            ]),
            'approved_by_employee' => $this->whenLoaded('approvedByEmployee', fn() => [
                'id'        => $this->approvedByEmployee->uuid,
                'full_name' => $this->approvedByEmployee->full_name,
            ]),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
