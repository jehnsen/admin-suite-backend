<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Training extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'training_title',
        'description',
        'training_type',
        'conducted_by',
        'venue',
        'venue_type',
        'date_from',
        'date_to',
        'number_of_hours',
        'ld_units',
        'certificate_number',
        'certificate_date',
        'certificate_file_path',
        'sponsorship',
        'cost',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'certificate_date' => 'date',
        'number_of_hours' => 'decimal:2',
        'ld_units' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the employee that attended this training.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate the duration of the training in days.
     */
    public function getDurationInDays(): int
    {
        return $this->date_from->diffInDays($this->date_to) + 1;
    }

    /**
     * Check if training is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'Completed';
    }

    /**
     * Check if training is ongoing.
     */
    public function isOngoing(): bool
    {
        return $this->status === 'Ongoing';
    }

    /**
     * Check if training has certificate.
     */
    public function hasCertificate(): bool
    {
        return !empty($this->certificate_number) || !empty($this->certificate_file_path);
    }

    /**
     * Scope a query to only include completed trainings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope a query to only include ongoing trainings.
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'Ongoing');
    }

    /**
     * Scope a query to filter by training type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('training_type', $type);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('date_from', [$dateFrom, $dateTo]);
    }

    /**
     * Scope a query to filter by year.
     */
    public function scopeByYear($query, int $year)
    {
        return $query->whereYear('date_from', $year);
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'training_title', 'date_from', 'training_type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Training {$eventName}")
            ->useLogName('hr');
    }
}
