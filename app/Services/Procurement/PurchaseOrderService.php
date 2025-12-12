<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\PurchaseOrderRepositoryInterface;
use App\Interfaces\Procurement\PurchaseRequestRepositoryInterface;
use App\Interfaces\Procurement\QuotationRepositoryInterface;
use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    protected $poRepository;
    protected $prRepository;
    protected $quotationRepository;
    protected $supplierRepository;

    public function __construct(
        PurchaseOrderRepositoryInterface $poRepository,
        PurchaseRequestRepositoryInterface $prRepository,
        QuotationRepositoryInterface $quotationRepository,
        SupplierRepositoryInterface $supplierRepository
    ) {
        $this->poRepository = $poRepository;
        $this->prRepository = $prRepository;
        $this->quotationRepository = $quotationRepository;
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Get all purchase orders with filters
     */
    public function getAllPurchaseOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->poRepository->getAllPurchaseOrders($filters, $perPage);
    }

    /**
     * Get purchase order by ID
     */
    public function getPurchaseOrderById(int $id): ?PurchaseOrder
    {
        return $this->poRepository->getPurchaseOrderById($id);
    }

    /**
     * Create new purchase order from approved PR and selected quotation
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            // Validate PR
            $pr = $this->prRepository->getPurchaseRequestById($data['purchase_request_id']);

            if (!$pr || !in_array($pr->status, ['Approved', 'For PO Creation'])) {
                throw new \Exception('Purchase request must be approved before creating PO.');
            }

            // Get selected quotation if applicable
            $quotation = null;
            if (!empty($data['quotation_id'])) {
                $quotation = $this->quotationRepository->getQuotationById($data['quotation_id']);

                if (!$quotation || !$quotation->is_selected) {
                    throw new \Exception('Quotation must be selected before creating PO.');
                }
            }

            // Get supplier details
            $supplier = $this->supplierRepository->getSupplierById($data['supplier_id']);

            if (!$supplier || !$supplier->isActive()) {
                throw new \Exception('Supplier must be active to create PO.');
            }

            // Generate PO number if not provided
            if (empty($data['po_number'])) {
                $data['po_number'] = $this->generatePONumber();
            }

            // Copy fund source from PR if not provided
            if (empty($data['fund_source'])) {
                $data['fund_source'] = $pr->fund_source;
            }

            // Create PO
            $po = $this->poRepository->createPurchaseOrder($data);

            // Update PR status
            $this->prRepository->updateStatus($pr->id, 'Completed');

            return $po;
        });
    }

    /**
     * Update purchase order
     */
    public function updatePurchaseOrder(int $id, array $data): PurchaseOrder
    {
        $po = $this->poRepository->getPurchaseOrderById($id);

        if ($po->status !== 'Pending') {
            throw new \Exception('Only pending purchase orders can be updated.');
        }

        return $this->poRepository->updatePurchaseOrder($id, $data);
    }

    /**
     * Delete purchase order
     */
    public function deletePurchaseOrder(int $id): bool
    {
        $po = $this->poRepository->getPurchaseOrderById($id);

        if ($po->status !== 'Pending') {
            throw new \Exception('Only pending purchase orders can be deleted.');
        }

        return $this->poRepository->deletePurchaseOrder($id);
    }

    /**
     * Approve purchase order
     */
    public function approvePurchaseOrder(int $id, int $approvedBy): PurchaseOrder
    {
        $po = $this->poRepository->getPurchaseOrderById($id);

        if (!$po->canBeApproved()) {
            throw new \Exception('Purchase order cannot be approved in current status.');
        }

        return $this->poRepository->approvePurchaseOrder($id, $approvedBy);
    }

    /**
     * Send PO to supplier
     */
    public function sendToSupplier(int $id): PurchaseOrder
    {
        $po = $this->poRepository->getPurchaseOrderById($id);

        if ($po->status !== 'Approved') {
            throw new \Exception('Only approved purchase orders can be sent to supplier.');
        }

        return $this->poRepository->updateStatus($id, 'Sent to Supplier');
    }

    /**
     * Cancel purchase order
     */
    public function cancelPurchaseOrder(int $id, string $reason): PurchaseOrder
    {
        $po = $this->poRepository->getPurchaseOrderById($id);

        if (in_array($po->status, ['Fully Delivered', 'Completed'])) {
            throw new \Exception('Cannot cancel completed purchase order.');
        }

        return $this->poRepository->updateStatus($id, 'Cancelled');
    }

    /**
     * Get pending purchase orders
     */
    public function getPendingPurchaseOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->poRepository->getPendingPurchaseOrders($perPage);
    }

    /**
     * Get purchase orders by supplier
     */
    public function getBySupplier(int $supplierId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->poRepository->getBySupplier($supplierId, $perPage);
    }

    /**
     * Get purchase order statistics
     */
    public function getPurchaseOrderStatistics(): array
    {
        return $this->poRepository->getPurchaseOrderStatistics();
    }

    /**
     * Generate unique PO number
     */
    private function generatePONumber(): string
    {
        $year = date('Y');
        $lastPO = PurchaseOrder::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPO ? ((int) substr($lastPO->po_number, -4)) + 1 : 1;

        return 'PO-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
