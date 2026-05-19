<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RequisitionSlip extends Model
{
    use HasFactory, HasUuid, SoftDeletes, LogsActivity;

    protected $fillable = [
        'ris_number',
        'requested_by_employee_id',
        'approved_by_employee_id',
        'released_by_employee_id',
        'division_office',
        'purpose',
        'status',
        'requested_date',
        'approved_date',
        'released_date',
        'remarks',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'approved_date'  => 'date',
        'released_date'  => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "RIS {$this->ris_number} was {$eventName}");
    }

    public function requestedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id');
    }

    public function releasedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'released_by_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionSlipItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    public function isReleased(): bool
    {
        return $this->status === 'Released';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }
}
