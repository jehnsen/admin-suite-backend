<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'po_date',
        'purchase_request_id',
        'quotation_id',
        'supplier_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'fund_source',
        'fund_cluster',
        'budget_id',
        'delivery_location',
        'delivery_date',
        'delivery_terms',
        'payment_terms',
        'payment_method',
        'terms_and_conditions',
        'special_instructions',
        'status',
        'approved_by',
        'approved_at',
        'prepared_by',
        'remarks',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the purchase request
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the quotation (if any)
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Get the supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the budget
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the user who prepared this PO
     */
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Get the user who approved this PO
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all items in this PO
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get all deliveries for this PO
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Check if PO is fully delivered
     */
    public function isFullyDelivered(): bool
    {
        return $this->items()->where('delivery_status', '!=', 'Fully Delivered')->count() === 0;
    }

    /**
     * Check if PO can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'Pending';
    }
}
