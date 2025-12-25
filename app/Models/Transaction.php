<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'type',
        'category',
        'amount',
        'budget_id',
        'expense_id',
        'disbursement_id',
        'cash_advance_id',
        'purchase_order_id',
        'payer',
        'payee',
        'employee_id',
        'fund_source',
        'description',
        'reference_number',
        'payment_method',
        'status',
        'verified_by',
        'verified_at',
        'remarks',
        'attachments',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * Get the budget
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the expense
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the disbursement
     */
    public function disbursement()
    {
        return $this->belongsTo(Disbursement::class);
    }

    /**
     * Get the cash advance
     */
    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * Get the purchase order
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the verifier
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for income transactions
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'Income');
    }

    /**
     * Scope for expense transactions
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'Expense');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
