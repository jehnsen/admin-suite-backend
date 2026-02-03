<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryAdjustment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'adjustment_number',
        'adjustment_date',
        'inventory_item_id',
        'adjustment_type',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'reason',
        'supporting_document',
        'prepared_by',
        'approved_by',
        'approved_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the inventory item
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who prepared
     */
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Get the user who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all documents for this inventory adjustment
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Get activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['adjustment_number', 'inventory_item_id', 'adjustment_type', 'quantity_adjusted', 'status', 'approved_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Adjustment {$eventName}")
            ->useLogName('inventory');
    }

    /**
     * Check if adjustment can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if adjustment is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}
