<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Budget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'budget_code',
        'budget_name',
        'description',
        'fund_source',
        'classification',
        'fiscal_year',
        'quarter',
        'allocated_amount',
        'utilized_amount',
        'remaining_balance',
        'category',
        'sub_category',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'approved_at',
        'managed_by',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'utilized_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($budget) {
            // Auto-calculate remaining balance
            $budget->remaining_balance = $budget->allocated_amount - $budget->utilized_amount;
        });

        static::updating(function ($budget) {
            // Auto-recalculate remaining balance
            $budget->remaining_balance = $budget->allocated_amount - $budget->utilized_amount;
        });
    }

    /**
     * Get the employee who approved the budget.
     */
    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the employee managing the budget.
     */
    public function managedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'managed_by');
    }

    /**
     * Get all expenses under this budget.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get approved expenses.
     */
    public function approvedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class)
                    ->whereIn('status', ['Approved', 'Disbursed']);
    }

    /**
     * Calculate utilization percentage.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_amount == 0) {
            return 0;
        }

        return ($this->utilized_amount / $this->allocated_amount) * 100;
    }

    /**
     * Check if budget is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Active' &&
               now()->between($this->start_date, $this->end_date);
    }

    /**
     * Check if budget has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->remaining_balance >= $amount;
    }

    /**
     * Check if budget is nearly depleted (90% utilized).
     */
    public function isNearlyDepleted(): bool
    {
        return $this->utilization_percentage >= 90;
    }

    /**
     * Update utilized amount.
     */
    public function updateUtilization(): void
    {
        $this->utilized_amount = $this->approvedExpenses()->sum('amount');
        $this->remaining_balance = $this->allocated_amount - $this->utilized_amount;
        $this->save();
    }

    /**
     * Scope a query to only include active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to filter by fiscal year.
     */
    public function scopeByFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    /**
     * Scope a query to filter by fund source.
     */
    public function scopeByFundSource($query, string $fundSource)
    {
        return $query->where('fund_source', $fundSource);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fiscal_year', 'fund_source', 'allocated_amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Budget {$eventName}")
            ->useLogName('financial');
    }
}
