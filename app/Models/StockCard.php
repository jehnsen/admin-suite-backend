<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockCard extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'inventory_item_id',
        'transaction_date',
        'reference_number',
        'transaction_type',
        'source_destination',
        'quantity_in',
        'quantity_out',
        'balance',
        'unit_cost',
        'total_cost',
        'delivery_id',
        'issuance_id',
        'purchase_order_id',
        'processed_by',
        'remarks',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the inventory item
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the delivery (if applicable)
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the issuance (if applicable)
     */
    public function issuance()
    {
        return $this->belongsTo(Issuance::class);
    }

    /**
     * Get the purchase order (if applicable)
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the user who processed this transaction
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['inventory_item_id', 'transaction_type', 'quantity_in', 'quantity_out', 'balance'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Stock Card {$eventName}")
            ->useLogName('inventory');
    }
}
