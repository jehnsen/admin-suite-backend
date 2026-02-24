<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'purchase_order_item_id',
        'item_number',
        'item_description',
        'unit_of_measure',
        'quantity_ordered',
        'quantity_delivered',
        'quantity_accepted',
        'quantity_rejected',
        'item_condition',
        'inspection_notes',
        'serial_numbers',
        'batch_number',
        'expiry_date',
        'remarks',
    ];

    protected $casts = [
        'serial_numbers' => 'array',
        'expiry_date' => 'date',
    ];

    /**
     * Get the delivery
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the related PO item
     */
    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

}
