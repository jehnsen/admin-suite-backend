<?php

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->uuid,
            'pr_number'            => $this->pr_number,
            'pr_date'              => $this->pr_date?->format('Y-m-d'),
            'department'           => $this->department,
            'section'              => $this->section,
            'purpose'              => $this->purpose,
            'fund_source'          => $this->fund_source,
            'fund_cluster'         => $this->fund_cluster,
            'ppmp_reference'       => $this->ppmp_reference,
            'procurement_mode'     => $this->procurement_mode,
            'estimated_budget'     => (float) ($this->estimated_budget ?? 0),
            'total_amount'         => (float) ($this->total_amount ?? 0),
            'date_needed'          => $this->date_needed?->format('Y-m-d'),
            'delivery_date'        => $this->delivery_date?->format('Y-m-d'),
            'delivery_location'    => $this->delivery_location,
            'status'               => $this->status,
            'can_be_edited'        => $this->canBeEdited(),
            'can_be_approved'      => $this->canBeApproved(),
            'is_approved'          => $this->isApproved(),
            'terms_and_conditions' => $this->terms_and_conditions,
            'remarks'              => $this->remarks,

            // Workflow actors
            'requested_by'          => $this->whenLoaded('requestedBy', fn() => [
                'id'   => $this->requestedBy->uuid,
                'name' => $this->requestedBy->name,
            ]),
            'recommended_by'        => $this->whenLoaded('recommendedBy', fn() => [
                'id'   => $this->recommendedBy->uuid,
                'name' => $this->recommendedBy->name,
            ]),
            'recommended_at'        => $this->recommended_at?->format('Y-m-d H:i:s'),
            'recommendation_remarks' => $this->recommendation_remarks,
            'approved_by'           => $this->whenLoaded('approvedBy', fn() => [
                'id'   => $this->approvedBy->uuid,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at'           => $this->approved_at?->format('Y-m-d H:i:s'),
            'approval_remarks'      => $this->approval_remarks,
            'disapproved_by'        => $this->whenLoaded('disapprovedBy', fn() => [
                'id'   => $this->disapprovedBy->uuid,
                'name' => $this->disapprovedBy->name,
            ]),
            'disapproved_at'        => $this->disapproved_at?->format('Y-m-d H:i:s'),
            'disapproval_reason'    => $this->disapproval_reason,

            // Line items (loaded on show)
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'               => $item->uuid,
                'item_number'      => $item->item_number,
                'item_code'        => $item->item_code,
                'item_description' => $item->item_description,
                'unit_of_measure'  => $item->unit_of_measure,
                'quantity'         => $item->quantity,
                'unit_cost'        => (float) ($item->unit_cost ?? 0),
                'total_cost'       => (float) ($item->total_cost ?? 0),
                'specifications'   => $item->specifications,
            ])),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
