<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Liquidation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'liquidation_number',
        'liquidation_date',
        'cash_advance_id',
        'cash_advance_amount',
        'total_expenses',
        'amount_to_refund',
        'additional_cash_needed',
        'supporting_documents',
        'summary_of_expenses',
        'verified_by',
        'verified_at',
        'verification_remarks',
        'approved_by',
        'approved_at',
        'refund_date',
        'refund_or_number',
        'additional_payment_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'liquidation_date' => 'date',
        'refund_date' => 'date',
        'additional_payment_date' => 'date',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'cash_advance_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'amount_to_refund' => 'decimal:2',
        'additional_cash_needed' => 'decimal:2',
        'supporting_documents' => 'array',
    ];

    /**
     * Get the cash advance
     */
    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * Get liquidation items
     */
    public function items()
    {
        return $this->hasMany(LiquidationItem::class);
    }

    /**
     * Get the user who verified
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate amounts automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($liquidation) {
            $totalExpenses = $liquidation->items()->sum('amount');
            $liquidation->total_expenses = $totalExpenses;

            $difference = $liquidation->cash_advance_amount - $totalExpenses;

            if ($difference > 0) {
                $liquidation->amount_to_refund = $difference;
                $liquidation->additional_cash_needed = 0;
            } elseif ($difference < 0) {
                $liquidation->amount_to_refund = 0;
                $liquidation->additional_cash_needed = abs($difference);
            } else {
                $liquidation->amount_to_refund = 0;
                $liquidation->additional_cash_needed = 0;
            }

            $liquidation->saveQuietly();
        });
    }
}
