<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Delivery extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'delivery_receipt_number',
        'delivery_date',
        'delivery_time',
        'purchase_order_id',
        'supplier_id',
        'supplier_dr_number',
        'invoice_number',
        'invoice_date',
        'delivered_by_name',
        'delivered_by_contact',
        'vehicle_plate_number',
        'received_by',
        'received_at',
        'received_location',
        'inspected_by',
        'inspected_at',
        'inspection_result',
        'inspection_remarks',
        'accepted_by',
        'accepted_at',
        'acceptance_remarks',
        'status',
        'condition',
        'condition_notes',
        'attachments',
        'remarks',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'invoice_date' => 'date',
        'received_at' => 'datetime',
        'inspected_at' => 'datetime',
        'accepted_at' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * Get the purchase order
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who received this delivery
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the user who inspected this delivery
     */
    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Get the user who accepted this delivery
     */
    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Get all items in this delivery
     */
    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * Check if delivery can be inspected
     */
    public function canBeInspected(): bool
    {
        return $this->status === 'Pending Inspection';
    }

    /**
     * Check if delivery can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'Under Inspection' && $this->inspection_result === 'Passed';
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['delivery_receipt_number', 'purchase_order_id', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Delivery {$eventName}")
            ->useLogName('procurement');
    }
}
