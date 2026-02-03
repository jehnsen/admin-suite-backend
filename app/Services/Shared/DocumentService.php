<?php

namespace App\Services\Shared;

use App\Interfaces\Shared\DocumentRepositoryInterface;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    protected $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * Upload a document
     */
    public function uploadDocument(array $data, UploadedFile $file): Document
    {
        // Validate document type is allowed for entity
        $this->validateDocumentTypeForEntity($data['documentable_type'], $data['document_type']);

        return $this->documentRepository->uploadDocument($data, $file);
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(int $id): ?Document
    {
        return $this->documentRepository->getDocumentById($id);
    }

    /**
     * Get all documents for a specific entity
     */
    public function getDocumentsByEntity(string $type, int $id): Collection
    {
        return $this->documentRepository->getDocumentsByEntity($type, $id);
    }

    /**
     * Delete a document
     */
    public function deleteDocument(int $id): bool
    {
        return $this->documentRepository->deleteDocument($id);
    }

    /**
     * Download a document
     */
    public function downloadDocument(int $id): StreamedResponse
    {
        return $this->documentRepository->downloadDocument($id);
    }

    /**
     * Check if entity has mandatory documents
     */
    public function hasMandatoryDocuments(string $entityType, int $entityId): bool
    {
        $mandatoryTypes = $this->getMandatoryDocumentTypes($entityType);

        if (empty($mandatoryTypes)) {
            return true; // No mandatory documents required
        }

        foreach ($mandatoryTypes as $docType) {
            $hasDocument = $this->documentRepository
                ->getDocumentsByType($entityType, $entityId, $docType)
                ->isNotEmpty();

            if (!$hasDocument) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate mandatory documents before approval
     */
    public function validateMandatoryDocuments(string $entityType, int $entityId): void
    {
        $mandatoryTypes = $this->getMandatoryDocumentTypes($entityType);

        foreach ($mandatoryTypes as $docType) {
            $hasDocument = $this->documentRepository
                ->getDocumentsByType($entityType, $entityId, $docType)
                ->isNotEmpty();

            if (!$hasDocument) {
                $readableType = $this->getReadableDocumentType($docType);
                throw new \Exception("{$readableType} is required before approval.");
            }
        }
    }

    /**
     * Get allowed document types for entity
     */
    public function getAllowedDocumentTypes(string $entityType): array
    {
        return match ($entityType) {
            'Liquidation' => ['official_receipt', 'other'],
            'PurchaseRequest' => ['purchase_order', 'delivery_receipt', 'iar'],
            'InventoryAdjustment' => ['property_card_photo', 'iar', 'other'],
            default => [],
        };
    }

    /**
     * Get mandatory document types for entity
     */
    public function getMandatoryDocumentTypes(string $entityType): array
    {
        return match ($entityType) {
            'Liquidation' => ['official_receipt'],
            'PurchaseRequest' => [],
            'InventoryAdjustment' => [],
            default => [],
        };
    }

    /**
     * Validate document type is allowed for entity
     */
    private function validateDocumentTypeForEntity(string $entityType, string $documentType): void
    {
        $allowedTypes = $this->getAllowedDocumentTypes($entityType);

        if (!in_array($documentType, $allowedTypes)) {
            throw new \Exception("Document type '{$documentType}' is not allowed for {$entityType}.");
        }
    }

    /**
     * Get human-readable document type name
     */
    private function getReadableDocumentType(string $docType): string
    {
        return match ($docType) {
            'official_receipt' => 'Official Receipt photo',
            'purchase_order' => 'Purchase Order',
            'delivery_receipt' => 'Delivery Receipt',
            'property_card_photo' => 'Property Card photo',
            'iar' => 'Inspection and Acceptance Report',
            'other' => 'Supporting document',
            default => ucwords(str_replace('_', ' ', $docType)),
        };
    }
}
