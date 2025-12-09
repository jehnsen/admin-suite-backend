<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\ServiceRecordRepositoryInterface;
use App\Models\ServiceRecord;
use Illuminate\Database\Eloquent\Collection;

class ServiceRecordRepository implements ServiceRecordRepositoryInterface
{
    /**
     * Get all service records for an employee.
     */
    public function getServiceRecordsByEmployee(int $employeeId): Collection
    {
        return ServiceRecord::where('employee_id', $employeeId)
                           ->orderBy('date_from', 'desc')
                           ->get();
    }

    /**
     * Find service record by ID.
     */
    public function findServiceRecordById(int $id): ?ServiceRecord
    {
        return ServiceRecord::with('employee')->find($id);
    }

    /**
     * Create a new service record.
     */
    public function createServiceRecord(array $data): ServiceRecord
    {
        return ServiceRecord::create($data);
    }

    /**
     * Update service record.
     */
    public function updateServiceRecord(int $id, array $data): ServiceRecord
    {
        $serviceRecord = ServiceRecord::findOrFail($id);
        $serviceRecord->update($data);
        return $serviceRecord->fresh();
    }

    /**
     * Delete service record.
     */
    public function deleteServiceRecord(int $id): bool
    {
        $serviceRecord = ServiceRecord::findOrFail($id);
        return $serviceRecord->delete();
    }

    /**
     * Get current service record for employee.
     */
    public function getCurrentServiceRecord(int $employeeId): ?ServiceRecord
    {
        return ServiceRecord::where('employee_id', $employeeId)
                           ->current()
                           ->first();
    }

    /**
     * Get service records by action type.
     */
    public function getServiceRecordsByActionType(string $actionType): Collection
    {
        return ServiceRecord::byActionType($actionType)
                           ->with('employee')
                           ->orderBy('date_from', 'desc')
                           ->get();
    }

    /**
     * Close current service record (set date_to).
     */
    public function closeCurrentServiceRecord(int $employeeId, string $dateTo): ?ServiceRecord
    {
        $currentRecord = $this->getCurrentServiceRecord($employeeId);

        if ($currentRecord) {
            $currentRecord->update(['date_to' => $dateTo]);
            return $currentRecord->fresh();
        }

        return null;
    }

    /**
     * Get service history for employee ordered by date.
     */
    public function getServiceHistory(int $employeeId): Collection
    {
        return ServiceRecord::where('employee_id', $employeeId)
                           ->orderBy('date_from', 'asc')
                           ->get();
    }
}
