<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\PurchaseRequestRepositoryInterface;
use App\Models\PurchaseRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseRequestService
{
    protected $prRepository;

    public function __construct(PurchaseRequestRepositoryInterface $prRepository)
    {
        $this->prRepository = $prRepository;
    }

    /**
     * Get all purchase requests with filters
     */
    public function getAllPurchaseRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->prRepository->getAllPurchaseRequests($filters, $perPage);
    }

    /**
     * Get purchase request by ID
     */
    public function getPurchaseRequestById(int $id): ?PurchaseRequest
    {
        return $this->prRepository->getPurchaseRequestById($id);
    }

    /**
     * Create new purchase request
     */
    public function createPurchaseRequest(array $data): PurchaseRequest
    {
        // Generate PR number if not provided
        if (empty($data['pr_number'])) {
            $data['pr_number'] = $this->generatePRNumber();
        }

        // Set initial status
        if (empty($data['status'])) {
            $data['status'] = 'Draft';
        }

        return $this->prRepository->createPurchaseRequest($data);
    }

    /**
     * Update purchase request
     */
    public function updatePurchaseRequest(int $id, array $data): PurchaseRequest
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if (!$pr->canBeEdited()) {
            throw new \Exception('Purchase request cannot be edited in current status.');
        }

        return $this->prRepository->updatePurchaseRequest($id, $data);
    }

    /**
     * Delete purchase request
     */
    public function deletePurchaseRequest(int $id): bool
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if (!in_array($pr->status, ['Draft', 'Pending'])) {
            throw new \Exception('Only draft or pending purchase requests can be deleted.');
        }

        return $this->prRepository->deletePurchaseRequest($id);
    }

    /**
     * Submit purchase request for approval
     */
    public function submitPurchaseRequest(int $id): PurchaseRequest
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if ($pr->status !== 'Draft') {
            throw new \Exception('Only draft purchase requests can be submitted.');
        }

        if ($pr->items->isEmpty()) {
            throw new \Exception('Cannot submit purchase request without items.');
        }

        return $this->prRepository->updateStatus($id, 'Pending');
    }

    /**
     * Recommend purchase request
     */
    public function recommendPurchaseRequest(int $id, int $recommendedBy, ?string $remarks = null): PurchaseRequest
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if ($pr->status !== 'Pending') {
            throw new \Exception('Only pending purchase requests can be recommended.');
        }

        return $this->prRepository->updateStatus($id, 'Recommended', [
            'recommended_by' => $recommendedBy,
            'recommended_at' => now(),
            'recommendation_remarks' => $remarks,
        ]);
    }

    /**
     * Approve purchase request
     */
    public function approvePurchaseRequest(int $id, int $approvedBy, ?string $remarks = null): PurchaseRequest
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if (!$pr->canBeApproved()) {
            throw new \Exception('Purchase request must be recommended before approval.');
        }

        return DB::transaction(function () use ($id, $approvedBy, $remarks) {
            $pr = $this->prRepository->updateStatus($id, 'Approved', [
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'approval_remarks' => $remarks,
            ]);

            // Change status to "For Quotation" for procurement modes that require it
            if (in_array($pr->procurement_mode, ['Public Bidding', 'Shopping', 'Limited Source Bidding'])) {
                $pr = $this->prRepository->updateStatus($id, 'For Quotation');
            }

            return $pr;
        });
    }

    /**
     * Disapprove purchase request
     */
    public function disapprovePurchaseRequest(int $id, int $disapprovedBy, string $reason): PurchaseRequest
    {
        return $this->prRepository->updateStatus($id, 'Disapproved', [
            'disapproved_by' => $disapprovedBy,
            'disapproved_at' => now(),
            'disapproval_reason' => $reason,
        ]);
    }

    /**
     * Cancel purchase request
     */
    public function cancelPurchaseRequest(int $id, string $reason): PurchaseRequest
    {
        $pr = $this->prRepository->getPurchaseRequestById($id);

        if (in_array($pr->status, ['Completed', 'Cancelled'])) {
            throw new \Exception('Cannot cancel purchase request in current status.');
        }

        return $this->prRepository->updateStatus($id, 'Cancelled', [
            'remarks' => $reason,
        ]);
    }

    /**
     * Get pending purchase requests
     */
    public function getPendingPurchaseRequests(int $perPage = 15): LengthAwarePaginator
    {
        return $this->prRepository->getPendingPurchaseRequests($perPage);
    }

    /**
     * Get purchase requests by requestor
     */
    public function getByRequestor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->prRepository->getByRequestor($userId, $perPage);
    }

    /**
     * Get purchase request statistics
     */
    public function getPurchaseRequestStatistics(): array
    {
        return $this->prRepository->getPurchaseRequestStatistics();
    }

    /**
     * Generate unique PR number
     */
    private function generatePRNumber(): string
    {
        $year = date('Y');
        $lastPR = PurchaseRequest::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPR ? ((int) substr($lastPR->pr_number, -4)) + 1 : 1;

        return 'PR-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
