<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceRecord extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'date_from',
        'date_to',
        'designation',
        'status_of_appointment',
        'salary_grade',
        'step_increment',
        'monthly_salary',
        'station_place_of_assignment',
        'office_entity',
        'government_service',
        'action_type',
        'appointment_authority',
        'appointment_date',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'appointment_date' => 'date',
        'monthly_salary' => 'decimal:2',
    ];

    /**
     * Get the employee that owns the service record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if this is the current position.
     */
    public function isCurrent(): bool
    {
        return $this->date_to === null;
    }

    /**
     * Get the duration of service in this position.
     */
    public function getDurationAttribute(): int
    {
        $endDate = $this->date_to ?? now();
        return $this->date_from->diffInMonths($endDate);
    }

    /**
     * Scope a query to only include current positions.
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('date_to');
    }

    /**
     * Scope a query to filter by action type.
     */
    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'designation', 'date_from', 'date_to', 'salary_grade'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Service Record {$eventName}")
            ->useLogName('hr');
    }
}
