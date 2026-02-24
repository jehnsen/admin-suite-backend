<?php

namespace App\Services\Financial;

use App\Interfaces\Financial\DisbursementRepositoryInterface;
use App\Models\Disbursement;
use Illuminate\Pagination\LengthAwarePaginator;

class DisbursementService
{
    protected $disbursementRepository;

    public function __construct(DisbursementRepositoryInterface $disbursementRepository)
    {
        $this->disbursementRepository = $disbursementRepository;
    }

    public function getAllDisbursements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->disbursementRepository->getAllDisbursements($filters, $perPage);
    }

    public function getDisbursementById(int $id): ?Disbursement
    {
        return $this->disbursementRepository->getDisbursementById($id);
    }

    public function createDisbursement(array $data): Disbursement
    {
        // Generate DV number if not provided
        if (empty($data['dv_number'])) {
            $data['dv_number'] = $this->generateDVNumber();
        }

        // Set gross amount if not provided
        if (empty($data['gross_amount'])) {
            $data['gross_amount'] = $data['amount'];
        }

        return $this->disbursementRepository->createDisbursement($data);
    }

    public function updateDisbursement(int $id, array $data): Disbursement
    {
        $disbursement = $this->disbursementRepository->getDisbursementById($id);

        if ($disbursement->status !== 'Pending') {
            throw new \Exception('Only pending disbursements can be updated.');
        }

        return $this->disbursementRepository->updateDisbursement($id, $data);
    }

    public function deleteDisbursement(int $id): bool
    {
        $disbursement = $this->disbursementRepository->getDisbursementById($id);

        if ($disbursement->status !== 'Pending') {
            throw new \Exception('Only pending disbursements can be deleted.');
        }

        return $this->disbursementRepository->deleteDisbursement($id);
    }

    public function certifyDisbursement(int $id, int $certifiedBy): Disbursement
    {
        $disbursement = $this->disbursementRepository->getDisbursementById($id);

        if ($disbursement->status !== 'Pending') {
            throw new \Exception('Only pending disbursements can be certified.');
        }

        return $this->disbursementRepository->certifyDisbursement($id, $certifiedBy);
    }

    public function approveDisbursement(int $id, int $approvedBy): Disbursement
    {
        $disbursement = $this->disbursementRepository->getDisbursementById($id);

        if ($disbursement->status !== 'Certified') {
            throw new \Exception('Only certified disbursements can be approved.');
        }

        return $this->disbursementRepository->approveDisbursement($id, $approvedBy);
    }

    public function markAsPaid(int $id, int $paidBy): Disbursement
    {
        $disbursement = $this->disbursementRepository->getDisbursementById($id);

        if ($disbursement->status !== 'Approved') {
            throw new \Exception('Only approved disbursements can be marked as paid.');
        }

        return $this->disbursementRepository->markAsPaid($id, $paidBy);
    }

    public function getPendingDisbursements(int $perPage = 15): LengthAwarePaginator
    {
        return $this->disbursementRepository->getPendingDisbursements($perPage);
    }

    public function getDisbursementStatistics(): array
    {
        return $this->disbursementRepository->getDisbursementStatistics();
    }

    private function generateDVNumber(): string
    {
        $year = date('Y');
        $lastDV = Disbursement::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastDV ? ((int) substr($lastDV->dv_number, -4)) + 1 : 1;

        return 'DV-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
