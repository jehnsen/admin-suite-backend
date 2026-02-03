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

        // Generate unique filename with timestamp
        $timestamp = time();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $fileName = $originalName . '_' . $timestamp . '.' . $extension;

        // Store file in public disk
        $filePath = $file->storeAs($storagePath, $fileName, 'public');

        // Create document record
        return Document::create([
            'documentable_id' => $data['documentable_id'],
            'documentable_type' => 'App\\Models\\' . $data['documentable_type'],
            'document_type' => $data['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $data['description'] ?? null,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'is_mandatory' => $this->isMandatoryDocument($data['documentable_type'], $data['document_type']),
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

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
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
}
