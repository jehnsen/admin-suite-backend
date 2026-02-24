<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\QuotationRepositoryInterface;
use App\Interfaces\Procurement\PurchaseRequestRepositoryInterface;
use App\Models\Quotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QuotationService
{
    protected $quotationRepository;
    protected $prRepository;

    public function __construct(
        QuotationRepositoryInterface $quotationRepository,
        PurchaseRequestRepositoryInterface $prRepository
    ) {
        $this->quotationRepository = $quotationRepository;
        $this->prRepository = $prRepository;
    }

    /**
     * Get all quotations with filters
     */
    public function getAllQuotations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->quotationRepository->getAllQuotations($filters, $perPage);
    }

    /**
     * Get quotation by ID
     */
    public function getQuotationById(int $id): ?Quotation
    {
        return $this->quotationRepository->getQuotationById($id);
    }

    /**
     * Get quotations by purchase request
     */
    public function getQuotationsByPurchaseRequest(int $prId): Collection
    {
        return $this->quotationRepository->getQuotationsByPurchaseRequest($prId);
    }

    /**
     * Create new quotation
     */
    public function createQuotation(array $data): Quotation
    {
        // Validate that PR exists and is in correct status
        $pr = $this->prRepository->getPurchaseRequestById($data['purchase_request_id']);

        if (!$pr) {
            throw new \Exception('Purchase request not found.');
        }

        if (!in_array($pr->status, ['Approved', 'For Quotation'])) {
            throw new \Exception('Purchase request must be approved before quotations can be created.');
        }

        // Generate quotation number if not provided
        if (empty($data['quotation_number'])) {
            $data['quotation_number'] = $this->generateQuotationNumber();
        }

        $quotation = $this->quotationRepository->createQuotation($data);

        // Update PR status to "For Quotation" if not already
        if ($pr->status === 'Approved') {
            $this->prRepository->updateStatus($pr->id, 'For Quotation');
        }

        return $quotation;
    }

    /**
     * Update quotation
     */
    public function updateQuotation(int $id, array $data): Quotation
    {
        $quotation = $this->quotationRepository->getQuotationById($id);

        if ($quotation->is_selected) {
            throw new \Exception('Cannot update selected quotation.');
        }

        return $this->quotationRepository->updateQuotation($id, $data);
    }

    /**
     * Delete quotation
     */
    public function deleteQuotation(int $id): bool
    {
        $quotation = $this->quotationRepository->getQuotationById($id);

        if ($quotation->is_selected) {
            throw new \Exception('Cannot delete selected quotation.');
        }

        return $this->quotationRepository->deleteQuotation($id);
    }

    /**
     * Select quotation (winning bid)
     */
    public function selectQuotation(int $id): Quotation
    {
        $quotation = $this->quotationRepository->getQuotationById($id);

        if (!$quotation->isValid()) {
            throw new \Exception('Quotation has expired.');
        }

        $quotation = $this->quotationRepository->selectQuotation($id);

        // Update PR status to "For PO Creation"
        $this->prRepository->updateStatus($quotation->purchase_request_id, 'For PO Creation');

        return $quotation;
    }

    /**
     * Evaluate quotations for a purchase request
     */
    public function evaluateQuotations(int $prId, array $evaluationData): bool
    {
        $pr = $this->prRepository->getPurchaseRequestById($prId);

        if ($pr->status !== 'For Quotation') {
            throw new \Exception('Purchase request is not in quotation stage.');
        }

        return $this->quotationRepository->evaluateQuotations($prId, $evaluationData);
    }

    /**
     * Generate unique quotation number
     */
    private function generateQuotationNumber(): string
    {
        $year = date('Y');
        $lastQuotation = Quotation::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastQuotation ? ((int) substr($lastQuotation->quotation_number, -4)) + 1 : 1;

        return 'QT-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
