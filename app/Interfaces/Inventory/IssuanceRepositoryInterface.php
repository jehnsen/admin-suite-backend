<?php

namespace App\Interfaces\Inventory;

use App\Models\Issuance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface IssuanceRepositoryInterface
{
    public function getAll(array $filters, int $perPage): LengthAwarePaginator;
    public function getById(int $id): ?Issuance;
    public function create(array $data): Issuance;
    public function update(int $id, array $data): Issuance;
    public function delete(int $id): bool;
    public function search(string $term, int $perPage): LengthAwarePaginator;
    public function getOverdue(int $perPage): LengthAwarePaginator;
    public function getByEmployee(int $employeeId, int $perPage): LengthAwarePaginator;
    public function acknowledge(int $id, array $data): Issuance;
    public function recordReturn(int $id, array $data): Issuance;
    public function transfer(int $id, int $newEmployeeId, string $remarks): Issuance;
    public function getStatistics(): array;
    public function generateIssuanceNumber(string $type): string;
    public function createBatch(array $shared, array $items): \Illuminate\Support\Collection;
}
