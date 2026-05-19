<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTimeRecord extends Model
{
    use HasUuid, HasFactory;

    protected $fillable = [
        'employee_id',
        'import_batch_id',
        'log_date',
        'time_in',
        'time_out',
        'hours_worked',
        'late_minutes',
        'undertime_minutes',
        'is_absent',
        'is_half_day',
        'is_holiday',
        'is_rest_day',
        'is_manually_corrected',
        'correction_reason',
        'corrected_by',
        'corrected_at',
    ];

    protected $casts = [
        'log_date'              => 'date',
        'hours_worked'          => 'decimal:2',
        'late_minutes'          => 'integer',
        'undertime_minutes'     => 'integer',
        'is_absent'             => 'boolean',
        'is_half_day'           => 'boolean',
        'is_holiday'            => 'boolean',
        'is_rest_day'           => 'boolean',
        'is_manually_corrected' => 'boolean',
        'corrected_at'          => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'import_batch_id');
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('log_date', $year)->whereMonth('log_date', $month);
    }

    public function scopeInRange($query, string $start, string $end)
    {
        return $query->whereBetween('log_date', [$start, $end]);
    }

    public function scopePresent($query)
    {
        return $query->where('is_absent', false)->where('is_rest_day', false)->where('is_holiday', false);
    }
}
