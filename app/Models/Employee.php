<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

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
}
