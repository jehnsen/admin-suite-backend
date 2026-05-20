<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\IssuanceRepositoryInterface;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Issuance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        return $this->issuanceRepository->create($this->resolveUuidsToIds($data));
    }

    public function update(int $id, array $data): Issuance
    {
        return $this->issuanceRepository->update($id, $this->resolveUuidsToIds($data));
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

    public function transfer(int $id, string $newEmployeeUuid, string $remarks = ''): Issuance
    {
        $newEmployeeId = $this->resolveEmployeeId($newEmployeeUuid);
        return $this->issuanceRepository->transfer($id, $newEmployeeId, $remarks);
    }

    public function createBatch(array $shared, array $items): \Illuminate\Support\Collection
    {
        $shared = $this->resolveUuidsToIds($shared);

        $items = array_map(function (array $item) {
            if (isset($item['inventory_item_id'])) {
                $item['inventory_item_id'] = $this->resolveInventoryItemId($item['inventory_item_id']);
            }
            return $item;
        }, $items);

        return $this->issuanceRepository->createBatch($shared, $items);
    }

    private function resolveUuidsToIds(array $data): array
    {
        $employeeFields = ['issued_to_employee_id', 'issued_by', 'approved_by'];
        foreach ($employeeFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = $this->resolveEmployeeId($data[$field]);
            }
        }

        if (!empty($data['inventory_item_id'])) {
            $data['inventory_item_id'] = $this->resolveInventoryItemId($data['inventory_item_id']);
        }

        return $data;
    }

    private function resolveEmployeeId(string $uuid): int
    {
        $id = Employee::where('uuid', $uuid)->value('id');
        if (!$id) {
            throw new ModelNotFoundException("Employee [{$uuid}] not found.");
        }
        return $id;
    }

    private function resolveInventoryItemId(string $uuid): int
    {
        $id = InventoryItem::where('uuid', $uuid)->value('id');
        if (!$id) {
            throw new ModelNotFoundException("InventoryItem [{$uuid}] not found.");
        }
        return $id;
    }

    public function getStatistics(): array
    {
        return $this->issuanceRepository->getStatistics();
    }
}
