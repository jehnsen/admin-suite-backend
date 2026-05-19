<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionSlipItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_slip_id',
        'inventory_item_id',
        'stock_number',
        'description',
        'unit_of_measure',
        'unit_cost',
        'quantity_requested',
        'quantity_approved',
        'quantity_issued',
        'remarks',
    ];

    protected $casts = [
        'unit_cost'          => 'decimal:2',
        'quantity_requested' => 'integer',
        'quantity_approved'  => 'integer',
        'quantity_issued'    => 'integer',
    ];

    public function requisitionSlip(): BelongsTo
    {
        return $this->belongsTo(RequisitionSlip::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->unit_cost * ($this->quantity_issued ?? $this->quantity_approved ?? $this->quantity_requested);
    }
}
