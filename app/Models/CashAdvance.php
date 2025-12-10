<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashAdvance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ca_number',
        'ca_date',
        'employee_id',
        'user_id',
        'purpose',
        'project_activity',
        'amount',
        'fund_source',
        'budget_id',
        'date_needed',
        'due_date_liquidation',
        'approved_by',
        'approved_at',
        'released_by',
        'released_at',
        'liquidated_amount',
        'unliquidated_balance',
        'liquidation_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'ca_date' => 'date',
        'date_needed' => 'date',
        'due_date_liquidation' => 'date',
        'liquidation_date' => 'date',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
        'amount' => 'decimal:2',
        'liquidated_amount' => 'decimal:2',
        'unliquidated_balance' => 'decimal:2',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the budget
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the user who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who released
     */
    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * Get liquidations
     */
    public function liquidations()
    {
        return $this->hasMany(Liquidation::class);
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'Released' &&
               $this->due_date_liquidation < now()->toDateString();
    }

    /**
     * Check if can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'Pending';
    }
}
