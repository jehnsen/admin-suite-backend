<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'sick_leave_type',
        'illness',
        'reason',
        'status',
        'recommended_by',
        'recommended_at',
        'recommendation_remarks',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'disapproved_by',
        'disapproved_at',
        'disapproval_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'decimal:2',
        'recommended_at' => 'datetime',
        'approved_at' => 'datetime',
        'disapproved_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the leave request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the employee who recommended the leave.
     */
    public function recommender(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recommended_by');
    }

    /**
     * Get the employee who approved the leave.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the employee who disapproved the leave.
     */
    public function disapprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'disapproved_by');
    }

    /**
     * Check if leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    /**
     * Check if leave request can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['Pending', 'Recommended']);
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /**
     * Scope a query to filter by leave type.
     */
    public function scopeByLeaveType($query, string $leaveType)
    {
        return $query->where('leave_type', $leaveType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'leave_type', 'start_date', 'end_date', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Leave Request {$eventName}")
            ->useLogName('hr');
    }
}
