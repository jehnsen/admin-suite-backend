<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'budget_id',
        'expense_number',
        'expense_name',
        'description',
        'expense_date',
        'amount',
        'payment_method',
        'payee',
        'reference_number',
        'invoice_number',
        'receipt_number',
        'po_number',
        'category',
        'sub_category',
        'purpose',
        'project_name',
        'requested_by',
        'approved_by',
        'approved_at',
        'disbursed_by',
        'disbursed_at',
        'status',
        'requires_liquidation',
        'liquidation_deadline',
        'liquidated_at',
        'liquidation_status',
        'attachments',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'liquidation_deadline' => 'date',
        'liquidated_at' => 'date',
        'requires_liquidation' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * Get the budget for this expense.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the employee who requested the expense.
     */
    public function requestedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    /**
     * Get the employee who approved the expense.
     */
    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the employee who disbursed the expense.
     */
    public function disbursedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'disbursed_by');
    }

    /**
     * Check if expense is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending Approval';
    }

    /**
     * Check if expense is approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ['Approved', 'Disbursed']);
    }

    /**
     * Check if liquidation is overdue.
     */
    public function isLiquidationOverdue(): bool
    {
        return $this->requires_liquidation &&
               $this->liquidation_deadline &&
               $this->liquidation_deadline < now() &&
               $this->liquidation_status !== 'Completed';
    }

    /**
     * Scope a query to only include pending expenses.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending Approval');
    }

    /**
     * Scope a query to only include approved expenses.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['Approved', 'Disbursed']);
    }

    /**
     * Scope a query to only include disbursed expenses.
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 'Disbursed');
    }

    /**
     * Scope a query to filter by budget.
     */
    public function scopeByBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include overdue liquidations.
     */
    public function scopeOverdueLiquidation($query)
    {
        return $query->where('requires_liquidation', true)
                    ->where('liquidation_status', '!=', 'Completed')
                    ->whereNotNull('liquidation_deadline')
                    ->where('liquidation_deadline', '<', now());
    }
}
