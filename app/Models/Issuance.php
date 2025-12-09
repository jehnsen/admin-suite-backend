<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issuance extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_item_id',
        'issued_to_employee_id',
        'issuance_number',
        'issued_date',
        'expected_return_date',
        'actual_return_date',
        'purpose',
        'purpose_details',
        'custodianship_type',
        'status',
        'condition_on_return',
        'return_remarks',
        'issued_by',
        'approved_by',
        'acknowledged_at',
        'acknowledgement_signature_path',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issued_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Get the inventory item for this issuance.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the employee to whom the item was issued.
     */
    public function issuedToEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_to_employee_id');
    }

    /**
     * Get the employee who issued the item.
     */
    public function issuedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_by');
    }

    /**
     * Get the employee who approved the issuance.
     */
    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Check if issuance is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * Check if item is overdue for return.
     */
    public function isOverdue(): bool
    {
        return $this->expected_return_date &&
               $this->expected_return_date < now() &&
               $this->status === 'Active';
    }

    /**
     * Check if issuance is acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Scope a query to only include active issuances.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to only include overdue issuances.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Active')
                    ->whereNotNull('expected_return_date')
                    ->where('expected_return_date', '<', now());
    }

    /**
     * Scope a query to filter by employee.
     */
    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('issued_to_employee_id', $employeeId);
    }
}
