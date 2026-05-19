<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImportBatch extends Model
{
    use HasUuid, HasFactory;

    protected $fillable = [
        'uploaded_by',
        'file_name',
        'original_file_name',
        'period_start',
        'period_end',
        'record_count',
        'processed_count',
        'error_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'record_count'     => 'integer',
        'processed_count'  => 'integer',
        'error_count'      => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'import_batch_id');
    }

    public function dailyTimeRecords(): HasMany
    {
        return $this->hasMany(DailyTimeRecord::class, 'import_batch_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
