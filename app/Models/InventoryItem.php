<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_code',
        'item_name',
        'description',
        'category',
        'unit_of_measure',
        'serial_number',
        'property_number',
        'model',
        'brand',
        'unit_cost',
        'quantity',
        'total_cost',
        'fund_source',
        'supplier',
        'date_acquired',
        'po_number',
        'invoice_number',
        'condition',
        'status',
        'location',
        'estimated_useful_life_years',
        'depreciation_rate',
        'accumulated_depreciation',
        'book_value',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'date_acquired' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inventoryItem) {
            // Auto-calculate total cost
            $inventoryItem->total_cost = $inventoryItem->unit_cost * $inventoryItem->quantity;

            // Auto-calculate initial book value
            if (!$inventoryItem->book_value) {
                $inventoryItem->book_value = $inventoryItem->total_cost;
            }
        });
    }

    /**
     * Get all issuances for this item.
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(Issuance::class);
    }

    /**
     * Get active issuances for this item.
     */
    public function activeIssuances(): HasMany
    {
        return $this->hasMany(Issuance::class)->where('status', 'Active');
    }

    /**
     * Check if item is serviceable.
     */
    public function isServiceable(): bool
    {
        return $this->condition === 'Serviceable';
    }

    /**
     * Check if item is available for issuance.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'In Stock' && $this->isServiceable();
    }

    /**
     * Check if item is currently issued.
     */
    public function isIssued(): bool
    {
        return $this->status === 'Issued';
    }

    /**
     * Calculate current depreciation.
     */
    public function calculateDepreciation(): float
    {
        if (!$this->estimated_useful_life_years || !$this->depreciation_rate) {
            return 0;
        }

        $yearsElapsed = $this->date_acquired->diffInYears(now());
        $annualDepreciation = $this->total_cost * ($this->depreciation_rate / 100);

        return min($annualDepreciation * $yearsElapsed, $this->total_cost);
    }

    /**
     * Update book value based on depreciation.
     */
    public function updateBookValue(): void
    {
        $this->accumulated_depreciation = $this->calculateDepreciation();
        $this->book_value = max(0, $this->total_cost - $this->accumulated_depreciation);
        $this->save();
    }

    /**
     * Scope a query to only include serviceable items.
     */
    public function scopeServiceable($query)
    {
        return $query->where('condition', 'Serviceable');
    }

    /**
     * Scope a query to only include available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'In Stock')
                    ->where('condition', 'Serviceable');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by fund source.
     */
    public function scopeByFundSource($query, string $fundSource)
    {
        return $query->where('fund_source', $fundSource);
    }
}
