<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\IssuanceRepositoryInterface;
use App\Models\Issuance;
use Illuminate\Pagination\LengthAwarePaginator;

class IssuanceService
{
    protected $issuanceRepository;

    public function __construct(IssuanceRepositoryInterface $issuanceRepository)
    {
        $this->issuanceRepository = $issuanceRepository;
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->issuanceRepository->getAll($filters, $perPage);
    }

    public function getById(int $id): ?Issuance
    {
        return $this->issuanceRepository->getById($id);
    }

    public function create(array $data): Issuance
    {
        $type = $data['document_type'] ?? 'General';

        if (empty($data['issuance_number'])) {
            $data['issuance_number'] = $this->issuanceRepository->generateIssuanceNumber($type);
        }

        return $this->issuanceRepository->create($data);
    }

    public function update(int $id, array $data): Issuance
    {
        return $this->issuanceRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->issuanceRepository->delete($id);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->issuanceRepository->search($term, $perPage);
    }

    public function getOverdue(int $perPage = 15): LengthAwarePaginator
    {
        return $this->issuanceRepository->getOverdue($perPage);
    }

    public function getByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->issuanceRepository->getByEmployee($employeeId, $perPage);
    }

    public function acknowledge(int $id, array $data): Issuance
    {
        return $this->issuanceRepository->acknowledge($id, $data);
    }

    public function recordReturn(int $id, array $data): Issuance
    {
        return $this->issuanceRepository->recordReturn($id, $data);
    }

    public function transfer(int $id, int $newEmployeeId, string $remarks = ''): Issuance
    {
        return $this->issuanceRepository->transfer($id, $newEmployeeId, $remarks);
    }

    public function getStatistics(): array
    {
        return $this->issuanceRepository->getStatistics();
    }
}
