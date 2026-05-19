<?php

namespace App\Interfaces\Inventory;

use App\Models\RequisitionSlip;
use Illuminate\Pagination\LengthAwarePaginator;

interface RequisitionSlipRepositoryInterface
{
    public function getAll(array $filters, int $perPage): LengthAwarePaginator;
    public function getById(int $id): ?RequisitionSlip;
    public function create(array $data, array $items): RequisitionSlip;
    public function update(int $id, array $data, ?array $items): RequisitionSlip;
    public function delete(int $id): bool;
    public function search(string $term, int $perPage): LengthAwarePaginator;
    public function getPending(int $perPage): LengthAwarePaginator;
    public function approve(int $id, int $approvedByEmployeeId, array $approvedQuantities): RequisitionSlip;
    public function release(int $id, int $releasedByEmployeeId, array $issuedQuantities): RequisitionSlip;
    public function cancel(int $id, string $remarks): RequisitionSlip;
    public function getStatistics(): array;
    public function generateRisNumber(): string;
}
