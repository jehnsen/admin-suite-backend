<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'item_number',
        'item_code',
        'item_description',
        'brand_model',
        'specifications',
        'unit_of_measure',
        'quantity_ordered',
        'quantity_delivered',
        'quantity_remaining',
        'unit_price',
        'total_price',
        'delivery_status',
        'remarks',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the purchase order
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the related PR item
     */
    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * Get delivery items for this PO item
     */
    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * Calculate totals and remaining quantity automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_price = $item->quantity_ordered * $item->unit_price;
            $item->quantity_remaining = $item->quantity_ordered - $item->quantity_delivered;

            // Update delivery status
            if ($item->quantity_delivered == 0) {
                $item->delivery_status = 'Pending';
            } elseif ($item->quantity_delivered >= $item->quantity_ordered) {
                $item->delivery_status = 'Fully Delivered';
            } else {
                $item->delivery_status = 'Partially Delivered';
            }
        });
    }
}
