<?php

namespace App\Services\Financial;

use App\Interfaces\Financial\LiquidationRepositoryInterface;
use App\Interfaces\Financial\CashAdvanceRepositoryInterface;
use App\Models\Liquidation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LiquidationService
{
    protected $liquidationRepository;
    protected $caRepository;
    protected $cashAdvanceService;

    public function __construct(
        LiquidationRepositoryInterface $liquidationRepository,
        CashAdvanceRepositoryInterface $caRepository,
        CashAdvanceService $cashAdvanceService
    ) {
        $this->liquidationRepository = $liquidationRepository;
        $this->caRepository = $caRepository;
        $this->cashAdvanceService = $cashAdvanceService;
    }

    public function getAllLiquidations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->liquidationRepository->getAllLiquidations($filters, $perPage);
    }

    public function getLiquidationById(int $id): ?Liquidation
    {
        return $this->liquidationRepository->getLiquidationById($id);
    }

    public function createLiquidation(array $data): Liquidation
    {
        // Validate cash advance exists and is released
        $ca = $this->caRepository->getCashAdvanceById($data['cash_advance_id']);

        if (!$ca) {
            throw new \Exception('Cash advance not found.');
        }

        if ($ca->status !== 'Released' && $ca->status !== 'Partially Liquidated') {
            throw new \Exception('Cash advance must be released before liquidation.');
        }

        // Generate liquidation number if not provided
        if (empty($data['liquidation_number'])) {
            $data['liquidation_number'] = $this->generateLiquidationNumber();
        }

        $data['cash_advance_amount'] = $ca->amount;

        return $this->liquidationRepository->createLiquidation($data);
    }

    public function updateLiquidation(int $id, array $data): Liquidation
    {
        $liquidation = $this->liquidationRepository->getLiquidationById($id);

        if (!in_array($liquidation->status, ['Pending', 'Under Review'])) {
            throw new \Exception('Only pending or under review liquidations can be updated.');
        }

        return $this->liquidationRepository->updateLiquidation($id, $data);
    }

    public function deleteLiquidation(int $id): bool
    {
        $liquidation = $this->liquidationRepository->getLiquidationById($id);

        if ($liquidation->status !== 'Pending') {
            throw new \Exception('Only pending liquidations can be deleted.');
        }

        return $this->liquidationRepository->deleteLiquidation($id);
    }

    public function verifyLiquidation(int $id, int $verifiedBy, ?string $remarks = null): Liquidation
    {
        $liquidation = $this->liquidationRepository->getLiquidationById($id);

        if (!in_array($liquidation->status, ['Pending', 'Under Review'])) {
            throw new \Exception('Only pending or under review liquidations can be verified.');
        }

        return $this->liquidationRepository->verifyLiquidation($id, $verifiedBy, $remarks);
    }

    public function approveLiquidation(int $id, int $approvedBy): Liquidation
    {
        return DB::transaction(function () use ($id, $approvedBy) {
            $liquidation = $this->liquidationRepository->getLiquidationById($id);

            if ($liquidation->status !== 'Verified') {
                throw new \Exception('Only verified liquidations can be approved.');
            }

            // Validate mandatory documents (Official Receipt)
            $hasOR = $liquidation->documents()
                ->where('document_type', 'official_receipt')
                ->exists();

            if (!$hasOR) {
                throw new \Exception('Official Receipt photo is required before approval.');
            }

            // Approve liquidation
            $liquidation = $this->liquidationRepository->approveLiquidation($id, $approvedBy);

            // Update cash advance liquidation status
            $this->cashAdvanceService->updateLiquidationStatus(
                $liquidation->cash_advance_id,
                $liquidation->total_expenses
            );

            return $liquidation;
        });
    }

    public function rejectLiquidation(int $id, string $reason): Liquidation
    {
        return $this->liquidationRepository->rejectLiquidation($id, $reason);
    }

    public function getPendingLiquidations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->liquidationRepository->getPendingLiquidations($perPage);
    }

    public function getLiquidationStatistics(): array
    {
        return $this->liquidationRepository->getLiquidationStatistics();
    }

    private function generateLiquidationNumber(): string
    {
        $year = date('Y');
        $lastLiquidation = Liquidation::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastLiquidation ? ((int) substr($lastLiquidation->liquidation_number, -4)) + 1 : 1;

        return 'LIQ-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
