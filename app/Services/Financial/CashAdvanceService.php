<?php

namespace App\Services\Financial;

use App\Interfaces\Financial\CashAdvanceRepositoryInterface;
use App\Models\CashAdvance;
use Illuminate\Pagination\LengthAwarePaginator;

class CashAdvanceService
{
    protected $caRepository;

    public function __construct(CashAdvanceRepositoryInterface $caRepository)
    {
        $this->caRepository = $caRepository;
    }

    public function getAllCashAdvances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();

        // Teachers/Staff can only see their own cash advances
        if ($user && $user->hasRole('Teacher/Staff') && $user->employee) {
            $filters['employee_id'] = $user->employee->id;
        }

        return $this->caRepository->getAllCashAdvances($filters, $perPage);
    }

    public function getCashAdvanceById(int $id): ?CashAdvance
    {
        return $this->caRepository->getCashAdvanceById($id);
    }

    public function createCashAdvance(array $data): CashAdvance
    {
        // Generate CA number if not provided
        if (empty($data['ca_number'])) {
            $data['ca_number'] = $this->generateCANumber();
        }

        return $this->caRepository->createCashAdvance($data);
    }

    public function updateCashAdvance(int $id, array $data): CashAdvance
    {
        $ca = $this->caRepository->getCashAdvanceById($id);

        if ($ca->status !== 'Pending') {
            throw new \Exception('Only pending cash advances can be updated.');
        }

        return $this->caRepository->updateCashAdvance($id, $data);
    }

    public function deleteCashAdvance(int $id): bool
    {
        $ca = $this->caRepository->getCashAdvanceById($id);

        if ($ca->status !== 'Pending') {
            throw new \Exception('Only pending cash advances can be deleted.');
        }

        return $this->caRepository->deleteCashAdvance($id);
    }

    public function approveCashAdvance(int $id, int $approvedBy): CashAdvance
    {
        $ca = $this->caRepository->getCashAdvanceById($id);

        if (!$ca->canBeApproved()) {
            throw new \Exception('Cash advance cannot be approved in current status.');
        }

        return $this->caRepository->approveCashAdvance($id, $approvedBy);
    }

    public function releaseCashAdvance(int $id, int $releasedBy): CashAdvance
    {
        $ca = $this->caRepository->getCashAdvanceById($id);

        if ($ca->status !== 'Approved') {
            throw new \Exception('Only approved cash advances can be released.');
        }

        return $this->caRepository->releaseCashAdvance($id, $releasedBy);
    }

    public function updateLiquidationStatus(int $id, float $liquidatedAmount): CashAdvance
    {
        $ca = $this->caRepository->getCashAdvanceById($id);

        $ca->liquidated_amount = $liquidatedAmount;
        $ca->unliquidated_balance = $ca->amount - $liquidatedAmount;

        if ($ca->unliquidated_balance <= 0) {
            $ca->status = 'Fully Liquidated';
            $ca->liquidation_date = now()->toDateString();
        } elseif ($liquidatedAmount > 0) {
            $ca->status = 'Partially Liquidated';
        }

        $ca->save();

        return $ca;
    }

    public function getOverdueCashAdvances(int $perPage = 15): LengthAwarePaginator
    {
        return $this->caRepository->getOverdueCashAdvances($perPage);
    }

    public function getPendingCashAdvances(int $perPage = 15): LengthAwarePaginator
    {
        return $this->caRepository->getPendingCashAdvances($perPage);
    }

    public function getByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->caRepository->getByEmployee($employeeId, $perPage);
    }

    public function getCashAdvanceStatistics(): array
    {
        return $this->caRepository->getCashAdvanceStatistics();
    }

    private function generateCANumber(): string
    {
        $year = date('Y');
        $lastCA = CashAdvance::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastCA ? ((int) substr($lastCA->ca_number, -4)) + 1 : 1;

        return 'CA-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
