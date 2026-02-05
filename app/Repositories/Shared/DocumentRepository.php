<?php

namespace App\Repositories\Shared;

use App\Interfaces\Shared\DocumentRepositoryInterface;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentRepository implements DocumentRepositoryInterface
{
    /**
     * Upload a document
     */
    public function uploadDocument(array $data, UploadedFile $file): Document
    {
        // Determine module folder based on entity type
        $module = $this->getModuleFolder($data['documentable_type']);

        // Generate storage path: documents/{module}/{year}/{month}
        $year = date('Y');
        $month = date('m');
        $storagePath = "documents/{$module}/{$year}/{$month}";

        // Sanitize original filename for safe storage
        $originalName = $this->sanitizeFilename($file->getClientOriginalName());
        $sanitizedBasename = pathinfo($originalName, PATHINFO_FILENAME);

        // Validate and get safe extension
        $extension = $this->validateAndGetExtension($file);

        // Generate unique filename with timestamp
        $timestamp = time();
        $fileName = $sanitizedBasename . '_' . $timestamp . '.' . $extension;

        // Determine if document is sensitive (stores in private disk)
        $isSensitive = $this->isSensitiveDocument($data['document_type'], $data['documentable_type']);
        $disk = $isSensitive ? 'private' : 'public';

        // Store file in appropriate disk (private for sensitive, public for non-sensitive)
        $filePath = $file->storeAs($storagePath, $fileName, $disk);

        // Create document record
        return Document::create([
            'documentable_id' => $data['documentable_id'],
            'documentable_type' => 'App\\Models\\' . $data['documentable_type'],
            'document_type' => $data['document_type'],
            'file_name' => $originalName, // Sanitized original name
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $data['description'] ?? null,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'is_mandatory' => $this->isMandatoryDocument($data['documentable_type'], $data['document_type']),
            'is_sensitive' => $isSensitive,
            'storage_disk' => $disk,
        ]);
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(int $id): ?Document
    {
        return Document::with(['uploadedBy', 'documentable'])->find($id);
    }

    /**
     * Get all documents for a specific entity
     */
    public function getDocumentsByEntity(string $type, int $id): Collection
    {
        return Document::where('documentable_type', 'App\\Models\\' . $type)
            ->where('documentable_id', $id)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete a document (soft delete)
     */
    public function deleteDocument(int $id): bool
    {
        $document = Document::find($id);

        if (!$document) {
            return false;
        }

        return $document->delete();
    }

    /**
     * Download a document
     */
    public function downloadDocument(int $id): StreamedResponse
    {
        $document = Document::findOrFail($id);

        // Determine which disk to use (defaults to public for backward compatibility)
        $disk = $document->storage_disk ?? 'public';

        // Check if file exists
        if (!Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'File not found on storage');
        }

        // Sanitize filename before sending to browser (defense in depth)
        $safeFilename = $this->sanitizeFilename($document->file_name);

        // Log document access for audit trail
        \Log::info('Document downloaded', [
            'document_id' => $document->id,
            'document_type' => $document->document_type,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'is_sensitive' => $document->is_sensitive ?? false,
        ]);

        return Storage::disk($disk)->download($document->file_path, $safeFilename);
    }

    /**
     * Get documents by type
     */
    public function getDocumentsByType(string $type, int $entityId, string $documentType): Collection
    {
        return Document::where('documentable_type', 'App\\Models\\' . $type)
            ->where('documentable_id', $entityId)
            ->where('document_type', $documentType)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Determine module folder based on entity type
     */
    private function getModuleFolder(string $entityType): string
    {
        return match ($entityType) {
            'Liquidation', 'CashAdvance', 'Disbursement' => 'liquidations',
            'PurchaseRequest', 'PurchaseOrder', 'Quotation', 'Delivery' => 'procurement',
            'InventoryAdjustment', 'InventoryItem', 'PhysicalCount' => 'inventory',
            default => 'other',
        };
    }

    /**
     * Determine if document type is mandatory for entity
     */
    private function isMandatoryDocument(string $entityType, string $documentType): bool
    {
        $mandatoryRules = [
            'Liquidation' => ['official_receipt'],
            'PurchaseRequest' => [],
            'InventoryAdjustment' => [],
        ];

        return in_array($documentType, $mandatoryRules[$entityType] ?? []);
    }

    /**
     * Sanitize filename to prevent security vulnerabilities
     *
     * Removes:
     * - Path traversal sequences (../, ..\)
     * - Null bytes
     * - Control characters
     * - Special characters that could cause issues
     * - Unicode direction override characters
     *
     * @param string $filename Original filename from client
     * @return string Sanitized filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove null bytes
        $filename = str_replace(chr(0), '', $filename);

        // Remove path traversal sequences
        $filename = str_replace(['../', '..\\', '../', '..\\'], '', $filename);

        // Remove directory separators
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);

        // Remove Unicode direction override characters (used in unicode attacks)
        $filename = preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $filename);

        // Remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);

        // Remove leading/trailing dots and spaces
        $filename = trim($filename, '. ');

        // Limit filename length (without extension) to prevent file system issues
        $pathInfo = pathinfo($filename);
        $basename = $pathInfo['filename'] ?? 'document';
        $extension = $pathInfo['extension'] ?? '';

        // Truncate basename to 100 characters
        if (strlen($basename) > 100) {
            $basename = substr($basename, 0, 100);
        }

        // If filename became empty after sanitization, use default
        if (empty($basename)) {
            $basename = 'document';
        }

        // Reconstruct filename
        return $extension ? $basename . '.' . $extension : $basename;
    }

    /**
     * Validate file extension and return safe extension
     *
     * @param UploadedFile $file
     * @return string Validated extension
     * @throws \Exception if extension is not allowed
     */
    private function validateAndGetExtension(UploadedFile $file): string
    {
        // Get extension from client
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Get extension from MIME type (more reliable)
        $mimeType = $file->getMimeType();
        $mimeExtension = $this->getExtensionFromMimeType($mimeType);

        // Allowed extensions (must match validation in UploadDocumentRequest)
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        // Verify client extension is allowed
        if (!in_array($clientExtension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension. Only PDF, JPG, JPEG, and PNG files are allowed.');
        }

        // Verify MIME type matches expected extension
        if ($mimeExtension && $mimeExtension !== $clientExtension) {
            // For JPEG, accept both jpg and jpeg
            if (!($mimeExtension === 'jpeg' && $clientExtension === 'jpg') &&
                !($mimeExtension === 'jpg' && $clientExtension === 'jpeg')) {
                throw new \Exception('File extension does not match file content.');
            }
        }

        return $clientExtension;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType
     * @return string|null Extension or null if unknown
     */
    private function getExtensionFromMimeType(string $mimeType): ?string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpeg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
    }

    /**
     * Determine if a document type is sensitive and requires private storage
     *
     * Sensitive documents contain financial, personal, or confidential information
     * and should not be publicly accessible.
     *
     * @param string $documentType The type of document
     * @param string $entityType The entity the document belongs to
     * @return bool True if document is sensitive
     */
    private function isSensitiveDocument(string $documentType, string $entityType): bool
    {
        // Sensitive document types (require private storage + auth)
        $sensitiveDocs = [
            'official_receipt',    // Contains financial transactions
            'purchase_order',      // Contains pricing and vendor info
            'delivery_receipt',    // Contains item quantities and values
            'iar',                 // Contains approval and acceptance data
        ];

        // All documents related to financial/liquidation are sensitive
        if (in_array($entityType, ['Liquidation', 'CashAdvance', 'Disbursement'])) {
            return true;
        }

        // Check if document type is in sensitive list
        return in_array($documentType, $sensitiveDocs);
    }

    /**
     * Generate temporary signed URL for document download
     *
     * Creates a time-limited URL that expires after specified duration.
     * Used for sensitive documents to prevent permanent public access.
     *
     * @param int $documentId Document ID
     * @param int $expiresInMinutes URL expiration time in minutes (default: 30)
     * @return string Temporary signed URL
     */
    public function generateTemporaryUrl(int $documentId, int $expiresInMinutes = 30): string
    {
        $document = Document::findOrFail($documentId);
        $disk = $document->storage_disk ?? 'public';

        // For public documents, return direct URL
        if ($disk === 'public' && !($document->is_sensitive ?? false)) {
            return Storage::disk('public')->url($document->file_path);
        }

        // For private/sensitive documents, generate temporary signed URL
        // Note: This requires the route to be defined with signed middleware
        return \URL::temporarySignedRoute(
            'documents.download',
            now()->addMinutes($expiresInMinutes),
            ['id' => $documentId]
        );
    }

    /**
     * Get document with temporary download URL
     *
     * @param int $id Document ID
     * @return array Document data with download URL
     */
    public function getDocumentWithUrl(int $id): array
    {
        $document = $this->getDocumentById($id);

        if (!$document) {
            return [];
        }

        return [
            'id' => $document->id,
            'documentable_type' => $document->documentable_type,
            'documentable_id' => $document->documentable_id,
            'document_type' => $document->document_type,
            'file_name' => $document->file_name,
            'file_size' => $document->file_size,
            'mime_type' => $document->mime_type,
            'description' => $document->description,
            'is_sensitive' => $document->is_sensitive ?? false,
            'is_mandatory' => $document->is_mandatory,
            'uploaded_by' => $document->uploadedBy?->name,
            'uploaded_at' => $document->uploaded_at,
            'download_url' => $this->generateTemporaryUrl($document->id, 30),
            'url_expires_in' => '30 minutes',
        ];
    }
}
