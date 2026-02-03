<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'documentable_id',
        'documentable_type',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
        'uploaded_at',
        'is_mandatory',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'file_size' => 'integer',
    ];

    protected $appends = ['file_url', 'file_size_human'];

    /**
     * Get the parent documentable model (Liquidation, PurchaseRequest, etc.)
     */
    public function documentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the document
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL for the document
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Boot method to handle file deletion when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete physical file when document is deleted (not soft deleted)
            if ($document->isForceDeleting() && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }
}
