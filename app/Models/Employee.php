<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'civil_status',
        'email',
        'mobile_number',
        'address',
        'city',
        'province',
        'zip_code',
        'plantilla_item_no',
        'position',
        'position_title',
        'salary_grade',
        'step_increment',
        'monthly_salary',
        'employment_status',
        'date_hired',
        'date_separated',
        'tin',
        'gsis_number',
        'philhealth_number',
        'pagibig_number',
        'sss_number',
        'vacation_leave_credits',
        'sick_leave_credits',
        'service_credit_balance',
        'standard_time_in',
        'standard_time_out',
        'daily_rate',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'date_hired' => 'date',
        'date_separated' => 'date',
        'monthly_salary' => 'decimal:2',
        'vacation_leave_credits' => 'decimal:2',
        'sick_leave_credits' => 'decimal:2',
        'service_credit_balance' => 'decimal:2',
        'daily_rate' => 'decimal:2',
    ];

    /**
     * Get the user associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all leave requests for the employee.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all service records for the employee.
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    /**
     * Get all trainings attended by the employee.
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    /**
     * Get all issuances where this employee is the recipient.
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(Issuance::class, 'issued_to_employee_id');
    }

    /**
     * Get all expenses requested by this employee.
     */
    public function expensesRequested(): HasMany
    {
        return $this->hasMany(Expense::class, 'requested_by');
    }

    /**
     * Get all budgets managed by this employee.
     */
    public function budgetsManaged(): HasMany
    {
        return $this->hasMany(Budget::class, 'managed_by');
    }

    /**
     * Get all attendance records for the employee.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Get all service credits for the employee.
     */
    public function serviceCredits(): HasMany
    {
        return $this->hasMany(ServiceCredit::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
    }

    /**
     * Get the employee's full name in last name first format.
     */
    public function getFullNameLastFirstAttribute(): string
    {
        $name = "{$this->last_name}, {$this->first_name}";
        if ($this->middle_name) {
            $name .= " {$this->middle_name}";
        }
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
    }

    /**
     * Calculate years of service.
     */
    public function getYearsOfServiceAttribute(): int
    {
        $endDate = $this->date_separated ?? now();
        return $this->date_hired->diffInYears($endDate);
    }

    /**
     * Check if employee is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to filter by position.
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Calculate daily rate from monthly salary.
     */
    public function calculateDailyRate(): float
    {
        return $this->monthly_salary ? round($this->monthly_salary / 22, 2) : 0.00;
    }

    /**
     * Check if employee is eligible for service credits.
     * Only Permanent and Active employees can earn service credits.
     */
    public function isEligibleForServiceCredits(): bool
    {
        return $this->employment_status === 'Permanent' && $this->isActive();
    }

    /**
     * Get attendance summary for current month.
     */
    public function getCurrentMonthAttendanceSummary(): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $records = $this->attendanceRecords()
            ->whereYear('attendance_date', $currentYear)
            ->whereMonth('attendance_date', $currentMonth)
            ->get();

        return [
            'total_days' => $records->count(),
            'present' => $records->where('status', 'Present')->count(),
            'absent' => $records->where('status', 'Absent')->count(),
            'late_count' => $records->where('late_minutes', '>', 0)->count(),
            'total_undertime_hours' => $records->sum('undertime_hours'),
            'total_overtime_hours' => $records->sum('overtime_hours'),
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_number', 'first_name', 'last_name', 'position',
                'employment_status', 'service_credit_balance'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Employee {$eventName}")
            ->useLogName('hr');
    }
}
