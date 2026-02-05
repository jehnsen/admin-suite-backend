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
        'is_sensitive',
        'storage_disk',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_sensitive' => 'boolean',
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
     *
     * For sensitive documents, returns a temporary signed URL
     * For public documents, returns direct storage URL
     */
    public function getFileUrlAttribute(): string
    {
        // For sensitive or private documents, use temporary signed route
        if (($this->is_sensitive ?? false) || ($this->storage_disk ?? 'public') === 'private') {
            return \URL::temporarySignedRoute(
                'documents.download',
                now()->addMinutes(30),
                ['id' => $this->id]
            );
        }

        // For public documents, return direct URL
        $disk = $this->storage_disk ?? 'public';
        return Storage::disk($disk)->url($this->file_path);
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
            if ($document->isForceDeleting()) {
                $disk = $document->storage_disk ?? 'public';
                if (Storage::disk($disk)->exists($document->file_path)) {
                    Storage::disk($disk)->delete($document->file_path);
                }
            }
        });
    }
}
