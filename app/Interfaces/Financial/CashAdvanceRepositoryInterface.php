<?php

namespace App\Interfaces\Financial;

use App\Models\CashAdvance;
use Illuminate\Pagination\LengthAwarePaginator;

interface CashAdvanceRepositoryInterface
{
    public function getAllCashAdvances(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getCashAdvanceById(int $id): ?CashAdvance;
    public function createCashAdvance(array $data): CashAdvance;
    public function updateCashAdvance(int $id, array $data): CashAdvance;
    public function deleteCashAdvance(int $id): bool;
    public function approveCashAdvance(int $id, int $approvedBy): CashAdvance;
    public function releaseCashAdvance(int $id, int $releasedBy): CashAdvance;
    public function getOverdueCashAdvances(int $perPage = 15): LengthAwarePaginator;
    public function getPendingCashAdvances(int $perPage = 15): LengthAwarePaginator;
    public function getByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator;
    public function getCashAdvanceStatistics(): array;
}
