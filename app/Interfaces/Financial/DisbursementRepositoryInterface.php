<?php

namespace App\Interfaces\Financial;

use App\Models\Disbursement;
use Illuminate\Pagination\LengthAwarePaginator;

interface DisbursementRepositoryInterface
{
    public function getAllDisbursements(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getDisbursementById(int $id): ?Disbursement;
    public function createDisbursement(array $data): Disbursement;
    public function updateDisbursement(int $id, array $data): Disbursement;
    public function deleteDisbursement(int $id): bool;
    public function certifyDisbursement(int $id, int $certifiedBy): Disbursement;
    public function approveDisbursement(int $id, int $approvedBy): Disbursement;
    public function markAsPaid(int $id, int $paidBy): Disbursement;
    public function getPendingDisbursements(int $perPage = 15): LengthAwarePaginator;
    public function getDisbursementStatistics(): array;
}
