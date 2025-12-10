<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dv_number',
        'dv_date',
        'payee_name',
        'payee_address',
        'payee_tin',
        'purchase_order_id',
        'expense_id',
        'cash_advance_id',
        'purpose',
        'amount',
        'fund_source',
        'budget_id',
        'payment_mode',
        'check_number',
        'check_date',
        'bank_name',
        'gross_amount',
        'tax_withheld',
        'net_amount',
        'certified_by',
        'certified_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'dv_date' => 'date',
        'check_date' => 'date',
        'certified_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'tax_withheld' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Get the purchase order (if applicable)
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the expense (if applicable)
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the cash advance (if applicable)
     */
    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * Get the budget
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the user who certified
     */
    public function certifiedBy()
    {
        return $this->belongsTo(User::class, 'certified_by');
    }

    /**
     * Get the user who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who paid
     */
    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Calculate net amount automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($disbursement) {
            if ($disbursement->gross_amount > 0) {
                $disbursement->net_amount = $disbursement->gross_amount - $disbursement->tax_withheld;
            } else {
                $disbursement->net_amount = $disbursement->amount;
            }
        });
    }
}
