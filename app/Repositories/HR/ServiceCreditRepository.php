<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\ServiceCreditRepositoryInterface;
use App\Models\ServiceCredit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ServiceCreditRepository implements ServiceCreditRepositoryInterface
{
    /**
     * Find service credit by ID.
     */
    public function findById(int $id): ?ServiceCredit
    {
        return ServiceCredit::with(['employee', 'approver', 'rejector', 'creator'])->find($id);
    }

    /**
     * Create a new service credit.
     */
    public function create(array $data): ServiceCredit
    {
        return ServiceCredit::create($data);
    }

    /**
     * Update service credit.
     */
    public function update(int $id, array $data): ServiceCredit
    {
        $credit = ServiceCredit::findOrFail($id);
        $credit->update($data);
        return $credit->fresh(['employee', 'approver', 'rejector', 'creator']);
    }

    /**
     * Delete service credit (soft delete).
     */
    public function delete(int $id): bool
    {
        $credit = ServiceCredit::findOrFail($id);
        return $credit->delete();
    }

    /**
     * Get service credits for a specific employee.
     */
    public function getByEmployee(int $employeeId, array $filters = []): Collection
    {
        $query = ServiceCredit::where('employee_id', $employeeId)
            ->with(['approver', 'rejector', 'creator']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['credit_type'])) {
            $query->where('credit_type', $filters['credit_type']);
        }

        return $query->orderBy('work_date', 'desc')->get();
    }

    /**
     * Get all service credits with pagination and filters.
     */
    public function getAllWithPagination(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ServiceCredit::with(['employee', 'approver', 'rejector', 'creator']);

        // Apply filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['credit_type'])) {
            $query->where('credit_type', $filters['credit_type']);
        }

        return $query->orderBy('work_date', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Get available credits for an employee (FIFO - oldest first).
     */
    public function getAvailableCredits(int $employeeId): Collection
    {
        return ServiceCredit::where('employee_id', $employeeId)
            ->available()
            ->orderBy('work_date', 'asc') // Oldest first for FIFO
            ->get();
    }

    /**
     * Get service credits pending approval.
     */
    public function getPendingApproval(): Collection
    {
        return ServiceCredit::with(['employee', 'creator'])
            ->where('status', 'Pending')
            ->orderBy('work_date', 'desc')
            ->get();
    }

    /**
     * Get service credits expiring soon.
     */
    public function getExpiringSoon(int $daysThreshold = 30): Collection
    {
        return ServiceCredit::with('employee')
            ->expiringSoon($daysThreshold)
            ->get();
    }

    /**
     * Get total available balance for an employee.
     */
    public function getTotalAvailableBalance(int $employeeId): float
    {
        return (float) ServiceCredit::where('employee_id', $employeeId)
            ->available()
            ->sum('credits_balance');
    }

    /**
     * Get earliest available credit for an employee.
     */
    public function getEarliestAvailableCredit(int $employeeId): ?ServiceCredit
    {
        return ServiceCredit::where('employee_id', $employeeId)
            ->available()
            ->orderBy('work_date', 'asc')
            ->first();
    }

    /**
     * Deduct credits from a specific service credit.
     */
    public function deductCredits(int $creditId, float $amount): ServiceCredit
    {
        $credit = ServiceCredit::findOrFail($creditId);

        DB::transaction(function () use ($credit, $amount) {
            $credit->credits_used += $amount;
            $credit->credits_balance -= $amount;
            $credit->save();
        });

        return $credit->fresh();
    }

    /**
     * Get credit summary for an employee.
     */
    public function getCreditSummary(int $employeeId): array
    {
        $credits = ServiceCredit::where('employee_id', $employeeId)->get();

        return [
            'total_earned' => round($credits->sum('credits_earned'), 2),
            'total_used' => round($credits->sum('credits_used'), 2),
            'total_balance' => round($credits->sum('credits_balance'), 2),
            'available_balance' => $this->getTotalAvailableBalance($employeeId),
            'pending_count' => $credits->where('status', 'Pending')->count(),
            'approved_count' => $credits->where('status', 'Approved')->count(),
            'expired_count' => $credits->filter(fn($c) => $c->isExpired())->count(),
        ];
    }
}
