<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasUuid, HasFactory;

    protected $fillable = [
        'employee_id',
        'import_batch_id',
        'log_date',
        'punched_at',
        'source',
    ];

    protected $casts = [
        'log_date'   => 'date',
        'punched_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'import_batch_id');
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('log_date', $date);
    }

    public function scopeInRange($query, string $start, string $end)
    {
        return $query->whereBetween('log_date', [$start, $end]);
    }
}
