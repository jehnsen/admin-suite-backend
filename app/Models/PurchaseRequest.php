<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pr_number',
        'pr_date',
        'requested_by',
        'department',
        'section',
        'purpose',
        'fund_source',
        'fund_cluster',
        'ppmp_reference',
        'procurement_mode',
        'estimated_budget',
        'total_amount',
        'date_needed',
        'delivery_date',
        'delivery_location',
        'status',
        'recommended_by',
        'recommended_at',
        'recommendation_remarks',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'disapproved_by',
        'disapproved_at',
        'disapproval_reason',
        'remarks',
        'terms_and_conditions',
    ];

    protected $casts = [
        'pr_date' => 'date',
        'date_needed' => 'date',
        'delivery_date' => 'date',
        'estimated_budget' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'recommended_at' => 'datetime',
        'approved_at' => 'datetime',
        'disapproved_at' => 'datetime',
    ];

    /**
     * Get the user who requested this PR
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who recommended this PR
     */
    public function recommendedBy()
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    /**
     * Get the user who approved this PR
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who disapproved this PR
     */
    public function disapprovedBy()
    {
        return $this->belongsTo(User::class, 'disapproved_by');
    }

    /**
     * Get all items in this purchase request
     */
    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    /**
     * Get all quotations for this PR
     */
    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Get the purchase order created from this PR
     */
    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /**
     * Check if PR can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['Draft', 'Pending']);
    }

    /**
     * Check if PR can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'Recommended';
    }

    /**
     * Check if PR is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}
