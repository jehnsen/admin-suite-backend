<?php

namespace App\Services\HR;

use App\Interfaces\HR\AttendanceRecordRepositoryInterface;
use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Interfaces\HR\ServiceCreditOffsetRepositoryInterface;
use App\Interfaces\HR\ServiceCreditRepositoryInterface;
use App\Models\ServiceCredit;
use App\Models\ServiceCreditOffset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceCreditService
{
    public function __construct(
        private ServiceCreditRepositoryInterface $serviceCreditRepository,
        private ServiceCreditOffsetRepositoryInterface $offsetRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRecordRepositoryInterface $attendanceRepository
    ) {}

    /**
     * Get all service credits with pagination and filters.
     */
    public function getAllServiceCredits(array $filters = [], int $perPage = 15)
    {
        return $this->serviceCreditRepository->getAllWithPagination($filters, $perPage);
    }

    /**
     * Get service credits for specific employee.
     */
    public function getEmployeeServiceCredits(int $employeeId, array $filters = [])
    {
        return $this->serviceCreditRepository->getByEmployee($employeeId, $filters);
    }

    /**
     * Find service credit by ID.
     */
    public function findServiceCreditById(int $id): ?ServiceCredit
    {
        return $this->serviceCreditRepository->findById($id);
    }

    /**
     * Create service credit with automatic calculations.
     */
    public function createServiceCredit(array $data): ServiceCredit
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['created_by'])) {
                $data['created_by'] = auth()->id();
            }

            // Validate employee exists and is eligible
            $employee = $this->employeeRepository->findEmployeeById($data['employee_id']);

            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            if (!$employee->isEligibleForServiceCredits()) {
                throw new \Exception('Only permanent employees can earn service credits.');
            }

            // Calculate credits earned (1 day = 8 hours = 1.0 credit)
            $data['credits_earned'] = round($data['hours_worked'] / 8, 2);
            $data['credits_balance'] = $data['credits_earned'];
            $data['credits_used'] = 0.00;
            $data['status'] = 'Pending';

            // Set expiry date (1 year from work_date)
            $data['expiry_date'] = Carbon::parse($data['work_date'])->addYear()->format('Y-m-d');

            return $this->serviceCreditRepository->create($data);
        });
    }

    /**
     * Update service credit.
     */
    public function updateServiceCredit(int $id, array $data): ServiceCredit
    {
        return DB::transaction(function () use ($id, $data) {
            $credit = $this->serviceCreditRepository->findById($id);

            if (!$credit) {
                throw new \Exception('Service credit not found.');
            }

            if (!$credit->canBeApproved()) {
                throw new \Exception('Cannot update service credit in current status.');
            }

            // Recalculate credits if hours changed
            if (isset($data['hours_worked'])) {
                $data['credits_earned'] = round($data['hours_worked'] / 8, 2);
                $data['credits_balance'] = $data['credits_earned'];
            }

            return $this->serviceCreditRepository->update($id, $data);
        });
    }

    /**
     * Delete service credit.
     */
    public function deleteServiceCredit(int $id): bool
    {
        $credit = $this->serviceCreditRepository->findById($id);

        if (!$credit) {
            throw new \Exception('Service credit not found.');
        }

        if ($credit->credits_used > 0) {
            throw new \Exception('Cannot delete service credit that has been used.');
        }

        return $this->serviceCreditRepository->delete($id);
    }

    /**
     * Approve service credit and update employee balance.
     */
    public function approveServiceCredit(int $id, int $approvedBy, ?string $remarks = null): ServiceCredit
    {
        return DB::transaction(function () use ($id, $approvedBy, $remarks) {
            $credit = $this->serviceCreditRepository->findById($id);

            if (!$credit->canBeApproved()) {
                throw new \Exception('Service credit cannot be approved.');
            }

            // Update employee service_credit_balance
            $employee = $credit->employee;
            $newBalance = $employee->service_credit_balance + $credit->credits_earned;

            $this->employeeRepository->updateEmployee($employee->id, [
                'service_credit_balance' => $newBalance
            ]);

            return $this->serviceCreditRepository->update($id, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'approval_remarks' => $remarks,
            ]);
        });
    }

    /**
     * Reject service credit.
     */
    public function rejectServiceCredit(int $id, int $rejectedBy, string $reason): ServiceCredit
    {
        return DB::transaction(function () use ($id, $rejectedBy, $reason) {
            $credit = $this->serviceCreditRepository->findById($id);

            if (!$credit->canBeApproved()) {
                throw new \Exception('Service credit cannot be rejected.');
            }

            return $this->serviceCreditRepository->update($id, [
                'status' => 'Rejected',
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }

    /**
     * Apply service credit to offset absence using FIFO (First-In-First-Out).
     * This is the CORE business logic for service credit application.
     */
    public function applyServiceCreditOffset(
        int $employeeId,
        int $attendanceRecordId,
        float $creditsNeeded,
        int $appliedBy
    ): array
    {
        return DB::transaction(function () use ($employeeId, $attendanceRecordId, $creditsNeeded, $appliedBy) {
            // Validate employee
            $employee = $this->employeeRepository->findEmployeeById($employeeId);
            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            // Check available balance
            if ($employee->service_credit_balance < $creditsNeeded) {
                throw new \Exception('Insufficient service credit balance.');
            }

            // Validate attendance record
            $attendanceRecord = $this->attendanceRepository->findById($attendanceRecordId);
            if (!$attendanceRecord) {
                throw new \Exception('Attendance record not found.');
            }

            // Get available credits (FIFO - oldest first)
            $availableCredits = $this->serviceCreditRepository
                ->getAvailableCredits($employeeId);

            if ($availableCredits->isEmpty()) {
                throw new \Exception('No available service credits found.');
            }

            $remainingNeeded = $creditsNeeded;
            $offsetsCreated = [];

            // FIFO Logic: Use oldest credits first
            foreach ($availableCredits as $credit) {
                if ($remainingNeeded <= 0) break;

                // Calculate how much to use from this credit
                $toUse = min($credit->credits_balance, $remainingNeeded);

                // Create offset record
                $offset = $this->offsetRepository->create([
                    'service_credit_id' => $credit->id,
                    'attendance_record_id' => $attendanceRecordId,
                    'employee_id' => $employeeId,
                    'credits_used' => $toUse,
                    'offset_date' => now()->toDateString(),
                    'status' => 'Applied',
                    'applied_by' => $appliedBy,
                ]);

                // Deduct from service credit
                $this->serviceCreditRepository->deductCredits($credit->id, $toUse);

                $remainingNeeded -= $toUse;
                $offsetsCreated[] = $offset;
            }

            // Update employee balance
            $this->employeeRepository->updateEmployee($employeeId, [
                'service_credit_balance' => $employee->service_credit_balance - $creditsNeeded
            ]);

            // Update attendance record status
            $this->attendanceRepository->update($attendanceRecordId, [
                'status' => 'Service Credit Used'
            ]);

            return [
                'credits_applied' => $creditsNeeded,
                'offsets_created' => count($offsetsCreated),
                'remaining_balance' => $employee->service_credit_balance - $creditsNeeded,
                'offsets' => $offsetsCreated,
            ];
        });
    }

    /**
     * Revert service credit offset (restore credits).
     */
    public function revertServiceCreditOffset(
        int $offsetId,
        int $revertedBy,
        string $reason
    ): ServiceCreditOffset
    {
        return DB::transaction(function () use ($offsetId, $revertedBy, $reason) {
            $offset = $this->offsetRepository->findById($offsetId);

            if (!$offset) {
                throw new \Exception('Offset not found.');
            }

            if (!$offset->canBeReverted()) {
                throw new \Exception('Offset cannot be reverted.');
            }

            // Restore credits to service_credit
            $credit = $offset->serviceCredit;
            $this->serviceCreditRepository->update($credit->id, [
                'credits_used' => $credit->credits_used - $offset->credits_used,
                'credits_balance' => $credit->credits_balance + $offset->credits_used,
            ]);

            // Update employee balance
            $employee = $offset->employee;
            $this->employeeRepository->updateEmployee($employee->id, [
                'service_credit_balance' => $employee->service_credit_balance + $offset->credits_used
            ]);

            // Update attendance record status back to original (if all offsets reverted)
            $attendanceOffsets = $this->offsetRepository->getByAttendanceRecord($offset->attendance_record_id);
            $activeOffsets = $attendanceOffsets->where('status', 'Applied')->count();

            if ($activeOffsets === 1) { // This is the last active offset
                $this->attendanceRepository->update($offset->attendance_record_id, [
                    'status' => 'Absent' // Or original status
                ]);
            }

            // Mark offset as reverted
            return $this->offsetRepository->update($offsetId, [
                'status' => 'Reverted',
                'reverted_at' => now(),
                'reverted_by' => $revertedBy,
                'revert_reason' => $reason,
            ]);
        });
    }

    /**
     * Get service credit summary for employee.
     */
    public function getEmployeeServiceCreditSummary(int $employeeId): array
    {
        return $this->serviceCreditRepository->getCreditSummary($employeeId);
    }

    /**
     * Get pending service credits.
     */
    public function getPendingServiceCredits()
    {
        return $this->serviceCreditRepository->getPendingApproval();
    }
}
