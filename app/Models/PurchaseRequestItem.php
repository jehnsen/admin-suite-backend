<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'item_number',
        'item_code',
        'item_description',
        'unit_of_measure',
        'quantity',
        'unit_cost',
        'total_cost',
        'specifications',
        'category',
        'stock_on_hand',
        'monthly_consumption',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the purchase request this item belongs to
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get quotation items for this PR item
     */
    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Get PO items for this PR item
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Calculate total cost automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_cost = $item->quantity * $item->unit_cost;
        });
    }
}
