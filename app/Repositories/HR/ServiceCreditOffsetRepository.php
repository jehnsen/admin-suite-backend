<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\ServiceCreditOffsetRepositoryInterface;
use App\Models\ServiceCreditOffset;
use Illuminate\Database\Eloquent\Collection;

class ServiceCreditOffsetRepository implements ServiceCreditOffsetRepositoryInterface
{
    /**
     * Find service credit offset by ID.
     */
    public function findById(int $id): ?ServiceCreditOffset
    {
        return ServiceCreditOffset::with([
            'serviceCredit',
            'attendanceRecord',
            'employee',
            'applier',
            'reverter'
        ])->find($id);
    }

    /**
     * Create a new service credit offset.
     */
    public function create(array $data): ServiceCreditOffset
    {
        return ServiceCreditOffset::create($data);
    }

    /**
     * Update service credit offset.
     */
    public function update(int $id, array $data): ServiceCreditOffset
    {
        $offset = ServiceCreditOffset::findOrFail($id);
        $offset->update($data);
        return $offset->fresh([
            'serviceCredit',
            'attendanceRecord',
            'employee',
            'applier',
            'reverter'
        ]);
    }

    /**
     * Get offsets for a specific service credit.
     */
    public function getByServiceCredit(int $creditId): Collection
    {
        return ServiceCreditOffset::with(['attendanceRecord', 'applier', 'reverter'])
            ->where('service_credit_id', $creditId)
            ->orderBy('offset_date', 'desc')
            ->get();
    }

    /**
     * Get offsets for a specific employee.
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return ServiceCreditOffset::with([
            'serviceCredit',
            'attendanceRecord',
            'applier',
            'reverter'
        ])
            ->where('employee_id', $employeeId)
            ->orderBy('offset_date', 'desc')
            ->get();
    }

    /**
     * Get offsets for a specific attendance record.
     */
    public function getByAttendanceRecord(int $recordId): Collection
    {
        return ServiceCreditOffset::with(['serviceCredit', 'applier', 'reverter'])
            ->where('attendance_record_id', $recordId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
