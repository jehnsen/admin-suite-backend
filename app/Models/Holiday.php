<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasUuid, HasFactory;

    protected $fillable = [
        'holiday_date',
        'holiday_name',
        'type',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('holiday_date', $year);
    }

    public function scopeInRange($query, string $start, string $end)
    {
        return $query->whereBetween('holiday_date', [$start, $end]);
    }
}
