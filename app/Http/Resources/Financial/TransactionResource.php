<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->uuid,
            'transaction_number' => $this->transaction_number,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'type'             => $this->type,
            'category'         => $this->category,
            'amount'           => (float) ($this->amount ?? 0),
            'fund_source'      => $this->fund_source,
            'description'      => $this->description,
            'reference_number' => $this->reference_number,
            'payment_method'   => $this->payment_method,
            'status'           => $this->status,
            'remarks'          => $this->remarks,
            // payer/payee (free text) intentionally omitted from list responses

            'budget' => $this->whenLoaded('budget', fn() => [
                'id'          => $this->budget->uuid,
                'budget_code' => $this->budget->budget_code,
                'budget_name' => $this->budget->budget_name,
            ]),
            'employee' => $this->whenLoaded('employee', fn() => [
                'id'        => $this->employee->uuid,
                'full_name' => $this->employee->full_name,
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
