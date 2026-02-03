<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'purchase_request_id',
        'supplier_id',
        'quotation_number',
        'quotation_date',
        'validity_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'payment_terms',
        'delivery_terms',
        'terms_and_conditions',
        'is_selected',
        'ranking',
        'evaluation_score',
        'evaluation_remarks',
        'status',
        'remarks',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'validity_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'evaluation_score' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    /**
     * Get the purchase request
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get all items in this quotation
     */
    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Get the purchase order created from this quotation
     */
    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /**
     * Check if quotation is still valid
     */
    public function isValid(): bool
    {
        return $this->validity_date === null || $this->validity_date >= now()->toDateString();
    }

    /**
     * Mark this quotation as selected
     */
    public function markAsSelected(): void
    {
        $this->is_selected = true;
        $this->status = 'Selected';
        $this->save();

        // Unselect other quotations for the same PR
        $this->purchaseRequest->quotations()
            ->where('id', '!=', $this->id)
            ->update(['is_selected' => false]);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quotation_number', 'supplier_id', 'total_amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Quotation {$eventName}")
            ->useLogName('procurement');
    }
}
