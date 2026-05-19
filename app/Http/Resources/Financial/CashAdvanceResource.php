<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->uuid,
            'ca_number'             => $this->ca_number,
            'ca_date'               => $this->ca_date?->format('Y-m-d'),
            'purpose'               => $this->purpose,
            'project_activity'      => $this->project_activity,
            'amount'                => (float) ($this->amount ?? 0),
            'fund_source'           => $this->fund_source,
            'date_needed'           => $this->date_needed?->format('Y-m-d'),
            'due_date_liquidation'  => $this->due_date_liquidation?->format('Y-m-d'),
            'liquidated_amount'     => (float) ($this->liquidated_amount ?? 0),
            'unliquidated_balance'  => (float) ($this->unliquidated_balance ?? 0),
            'liquidation_date'      => $this->liquidation_date?->format('Y-m-d'),
            'status'                => $this->status,
            'is_overdue'            => $this->isOverdue(),
            'can_be_approved'       => $this->canBeApproved(),
            'remarks'               => $this->remarks,

            'employee' => $this->whenLoaded('employee', fn() => [
                'id'        => $this->employee->uuid,
                'full_name' => $this->employee->full_name,
                'position'  => $this->employee->position,
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
            'released_by' => $this->whenLoaded('releasedBy', fn() => [
                'id'   => $this->releasedBy->uuid,
                'name' => $this->releasedBy->name,
            ]),
            'released_at' => $this->released_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
