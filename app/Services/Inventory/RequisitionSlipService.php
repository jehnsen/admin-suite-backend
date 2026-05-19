<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\RequisitionSlipRepositoryInterface;
use App\Models\RequisitionSlip;
use Illuminate\Pagination\LengthAwarePaginator;

class RequisitionSlipService
{
    protected $risRepository;

    public function __construct(RequisitionSlipRepositoryInterface $risRepository)
    {
        $this->risRepository = $risRepository;
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->risRepository->getAll($filters, $perPage);
    }

    public function getById(int $id): ?RequisitionSlip
    {
        return $this->risRepository->getById($id);
    }

    public function create(array $data, array $items): RequisitionSlip
    {
        if (empty($data['ris_number'])) {
            $data['ris_number'] = $this->risRepository->generateRisNumber();
        }

        return $this->risRepository->create($data, $items);
    }

    public function update(int $id, array $data, ?array $items = null): RequisitionSlip
    {
        return $this->risRepository->update($id, $data, $items);
    }

    public function delete(int $id): bool
    {
        return $this->risRepository->delete($id);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->risRepository->search($term, $perPage);
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return $this->risRepository->getPending($perPage);
    }

    public function approve(int $id, int $approvedByEmployeeId, array $approvedQuantities): RequisitionSlip
    {
        return $this->risRepository->approve($id, $approvedByEmployeeId, $approvedQuantities);
    }

    public function release(int $id, int $releasedByEmployeeId, array $issuedQuantities): RequisitionSlip
    {
        return $this->risRepository->release($id, $releasedByEmployeeId, $issuedQuantities);
    }

    public function cancel(int $id, string $remarks = ''): RequisitionSlip
    {
        return $this->risRepository->cancel($id, $remarks);
    }

    public function getStatistics(): array
    {
        return $this->risRepository->getStatistics();
    }
}
