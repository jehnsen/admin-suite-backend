<?php

namespace App\Interfaces\Shared;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface DocumentRepositoryInterface
{
    /**
     * Upload a document
     */
    public function uploadDocument(array $data, UploadedFile $file): Document;

    /**
     * Get document by ID
     */
    public function getDocumentById(int $id): ?Document;

    /**
     * Get all documents for a specific entity
     */
    public function getDocumentsByEntity(string $type, int $id): Collection;

    /**
     * Delete a document
     */
    public function deleteDocument(int $id): bool;

    /**
     * Download a document
     */
    public function downloadDocument(int $id): StreamedResponse;

    /**
     * Get documents by type
     */
    public function getDocumentsByType(string $type, int $entityId, string $documentType): Collection;
}
