<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'supplier_code',
        'business_name',
        'trade_name',
        'owner_name',
        'business_type',
        'email',
        'phone_number',
        'mobile_number',
        'address',
        'city',
        'province',
        'zip_code',
        'tin',
        'bir_certificate_number',
        'dti_registration',
        'sec_registration',
        'mayors_permit',
        'philgeps_registration',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'product_categories',
        'supplier_classification',
        'rating',
        'total_transactions',
        'total_amount_transacted',
        'status',
        'remarks',
    ];

    protected $casts = [
        'product_categories' => 'array',
        'rating' => 'decimal:2',
        'total_amount_transacted' => 'decimal:2',
    ];

    /**
     * Get all quotations for this supplier
     */
    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Get all purchase orders for this supplier
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get all deliveries from this supplier
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Check if supplier is active
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * Get display name (trade name or business name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->trade_name ?? $this->business_name;
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['business_name', 'owner_name', 'email', 'phone_number', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Supplier {$eventName}")
            ->useLogName('procurement');
    }
}
