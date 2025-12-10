<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'count_number',
        'count_date',
        'inventory_item_id',
        'system_quantity',
        'actual_quantity',
        'variance',
        'variance_type',
        'counted_by',
        'verified_by',
        'verified_at',
        'status',
        'variance_explanation',
        'corrective_action',
        'remarks',
    ];

    protected $casts = [
        'count_date' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the inventory item
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who counted
     */
    public function countedBy()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * Get the user who verified
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if there's a variance
     */
    public function hasVariance(): bool
    {
        return $this->variance != 0;
    }

    /**
     * Calculate variance automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($count) {
            $count->variance = $count->actual_quantity - $count->system_quantity;

            if ($count->variance < 0) {
                $count->variance_type = 'Shortage';
            } elseif ($count->variance > 0) {
                $count->variance_type = 'Overage';
            } else {
                $count->variance_type = 'Match';
            }
        });
    }
}
