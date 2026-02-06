<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceCredit extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'credit_type',
        'work_date',
        'description',
        'hours_worked',
        'credits_earned',
        'credits_used',
        'credits_balance',
        'status',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'created_by',
        'expiry_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'work_date' => 'date',
        'hours_worked' => 'decimal:2',
        'credits_earned' => 'decimal:2',
        'credits_used' => 'decimal:2',
        'credits_balance' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    /**
     * Get the employee that owns the service credit.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the employee who approved the service credit.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the employee who rejected the service credit.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rejected_by');
    }

    /**
     * Get the user who created the service credit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all offsets where this credit was used.
     */
    public function offsets(): HasMany
    {
        return $this->hasMany(ServiceCreditOffset::class);
    }

    /**
     * Check if credit is available for use.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'Approved'
            && $this->credits_balance > 0
            && !$this->isExpired();
    }

    /**
     * Check if credit is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)->isPast();
    }

    /**
     * Check if credit can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if credit can be used.
     */
    public function canBeUsed(): bool
    {
        return $this->isAvailable();
    }

    /**
     * Scope a query to only include approved credits.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /**
     * Scope a query to only include available credits.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'Approved')
                    ->where('credits_balance', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', now());
                    });
    }

    /**
     * Scope a query to filter by employee.
     */
    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to filter by credit type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('credit_type', $type);
    }

    /**
     * Scope a query to get credits expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $futureDate = now()->addDays($days);

        return $query->where('status', 'Approved')
                    ->where('credits_balance', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [now(), $futureDate]);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'credit_type', 'credits_earned', 'credits_balance', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Service Credit {$eventName}")
            ->useLogName('service_credits');
    }
}
