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

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'time_in',
        'time_out',
        'lunch_out',
        'lunch_in',
        'status',
        'undertime_hours',
        'late_minutes',
        'overtime_hours',
        'remarks',
        'import_source',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_date' => 'date',
        'undertime_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'overtime_hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the attendance record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created the attendance record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee who approved the attendance record.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get all service credit offsets for this attendance record.
     */
    public function serviceCreditOffsets(): HasMany
    {
        return $this->hasMany(ServiceCreditOffset::class);
    }

    /**
     * Calculate total work hours for the day.
     */
    public function getTotalWorkHoursAttribute(): float
    {
        if (!$this->time_in || !$this->time_out) {
            return 0.00;
        }

        $timeIn = Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $this->time_in);
        $timeOut = Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $this->time_out);

        // Calculate work hours
        $totalMinutes = $timeIn->diffInMinutes($timeOut);

        // Subtract lunch break if both lunch_out and lunch_in exist
        if ($this->lunch_out && $this->lunch_in) {
            $lunchOut = Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $this->lunch_out);
            $lunchIn = Carbon::parse($this->attendance_date->format('Y-m-d') . ' ' . $this->lunch_in);
            $lunchMinutes = $lunchOut->diffInMinutes($lunchIn);
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Check if attendance record is complete (has time_in and time_out).
     */
    public function isComplete(): bool
    {
        return !is_null($this->time_in) && !is_null($this->time_out);
    }

    /**
     * Check if record can be edited.
     */
    public function canBeEdited(): bool
    {
        return is_null($this->approved_at);
    }

    /**
     * Check if employee was late (time_in > standard_time_in).
     */
    public function isLate(): bool
    {
        return $this->late_minutes > 0;
    }

    /**
     * Check if record has undertime.
     */
    public function hasUndertime(): bool
    {
        return $this->undertime_hours > 0;
    }

    /**
     * Scope a query to get attendance for a specific month.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('attendance_date', $year)
                    ->whereMonth('attendance_date', $month);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include present records.
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'Present');
    }

    /**
     * Scope a query to only include records with undertime.
     */
    public function scopeWithUndertime($query)
    {
        return $query->where('undertime_hours', '>', 0);
    }

    /**
     * Scope a query to only include records pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->whereNull('approved_at');
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'attendance_date', 'status', 'undertime_hours', 'late_minutes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Attendance Record {$eventName}")
            ->useLogName('attendance');
    }
}
