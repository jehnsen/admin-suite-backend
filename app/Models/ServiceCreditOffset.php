<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceCreditOffset extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_credit_id',
        'attendance_record_id',
        'employee_id',
        'credits_used',
        'offset_date',
        'reason',
        'status',
        'applied_by',
        'reverted_at',
        'reverted_by',
        'revert_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credits_used' => 'decimal:2',
        'offset_date' => 'date',
        'reverted_at' => 'datetime',
    ];

    /**
     * Get the service credit that was used.
     */
    public function serviceCredit(): BelongsTo
    {
        return $this->belongsTo(ServiceCredit::class);
    }

    /**
     * Get the attendance record this offset was applied to.
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * Get the employee this offset belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who applied this offset.
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Get the user who reverted this offset.
     */
    public function reverter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    /**
     * Check if offset is active (not reverted).
     */
    public function isActive(): bool
    {
        return $this->status === 'Applied';
    }

    /**
     * Check if offset can be reverted.
     */
    public function canBeReverted(): bool
    {
        return $this->status === 'Applied';
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['service_credit_id', 'attendance_record_id', 'credits_used', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Service Credit Offset {$eventName}")
            ->useLogName('service_credits');
    }
}
