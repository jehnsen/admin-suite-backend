<?php

namespace App\Services\HR;

use App\Interfaces\HR\ServiceRecordRepositoryInterface;
use App\Models\ServiceRecord;
use Illuminate\Database\Eloquent\Collection;

class ServiceRecordService
{
    public function __construct(
        private ServiceRecordRepositoryInterface $serviceRecordRepository
    ) {}

    /**
     * Get all service records for an employee.
     */
    public function getServiceRecordsByEmployee(int $employeeId): Collection
    {
        return $this->serviceRecordRepository->getServiceRecordsByEmployee($employeeId);
    }

    /**
     * Get service history (ordered chronologically).
     */
    public function getServiceHistory(int $employeeId): Collection
    {
        return $this->serviceRecordRepository->getServiceHistory($employeeId);
    }

    /**
     * Find service record by ID.
     */
    public function findServiceRecordById(int $id): ?ServiceRecord
    {
        return $this->serviceRecordRepository->findServiceRecordById($id);
    }

    /**
     * Create a new service record.
     */
    public function createServiceRecord(array $data): ServiceRecord
    {
        return $this->serviceRecordRepository->createServiceRecord($data);
    }

    /**
     * Update service record.
     */
    public function updateServiceRecord(int $id, array $data): ServiceRecord
    {
        return $this->serviceRecordRepository->updateServiceRecord($id, $data);
    }

    /**
     * Get current position for employee.
     */
    public function getCurrentPosition(int $employeeId): ?ServiceRecord
    {
        return $this->serviceRecordRepository->getCurrentServiceRecord($employeeId);
    }

    /**
     * Calculate total government service years.
     */
    public function calculateTotalGovernmentService(int $employeeId): array
    {
        $records = $this->serviceRecordRepository->getServiceHistory($employeeId);

        $totalMonths = 0;

        foreach ($records as $record) {
            if ($record->government_service === 'Yes') {
                $startDate = $record->date_from;
                $endDate = $record->date_to ?? now();

                $totalMonths += $startDate->diffInMonths($endDate);
            }
        }

        $years = floor($totalMonths / 12);
        $months = $totalMonths % 12;

        return [
            'total_months' => $totalMonths,
            'years' => $years,
            'months' => $months,
            'formatted' => "{$years} year(s) and {$months} month(s)",
        ];
    }

    /**
     * Delete service record.
     */
    public function deleteServiceRecord(int $id): bool
    {
        return $this->serviceRecordRepository->deleteServiceRecord($id);
    }
}
