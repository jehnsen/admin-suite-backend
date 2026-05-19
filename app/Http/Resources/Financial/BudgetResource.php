<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->uuid,
            'budget_code'              => $this->budget_code,
            'budget_name'              => $this->budget_name,
            'description'              => $this->description,
            'fund_source'              => $this->fund_source,
            'classification'           => $this->classification,
            'fiscal_year'              => $this->fiscal_year,
            'quarter'                  => $this->quarter,
            'category'                 => $this->category,
            'sub_category'             => $this->sub_category,
            'allocated_amount'         => (float) ($this->allocated_amount ?? 0),
            'utilized_amount'          => (float) ($this->utilized_amount ?? 0),
            'remaining_balance'        => (float) ($this->remaining_balance ?? 0),
            'utilization_percentage'   => round((float) $this->utilization_percentage, 2),
            'start_date'               => $this->start_date?->format('Y-m-d'),
            'end_date'                 => $this->end_date?->format('Y-m-d'),
            'status'                   => $this->status,
            'is_active'                => $this->isActive(),
            'is_nearly_depleted'       => $this->isNearlyDepleted(),
            'remarks'                  => $this->remarks,

            'approved_by_employee' => $this->whenLoaded('approvedByEmployee', fn() => [
                'id'        => $this->approvedByEmployee->uuid,
                'full_name' => $this->approvedByEmployee->full_name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'managed_by_employee' => $this->whenLoaded('managedByEmployee', fn() => [
                'id'        => $this->managedByEmployee->uuid,
                'full_name' => $this->managedByEmployee->full_name,
            ]),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
