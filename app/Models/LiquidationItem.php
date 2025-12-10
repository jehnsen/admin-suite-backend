<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiquidationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'liquidation_id',
        'item_number',
        'expense_date',
        'particulars',
        'or_invoice_number',
        'amount',
        'category',
        'remarks',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the liquidation
     */
    public function liquidation()
    {
        return $this->belongsTo(Liquidation::class);
    }
}
