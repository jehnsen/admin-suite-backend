<?php

namespace App\Repositories\Financial;

use App\Interfaces\Financial\CashAdvanceRepositoryInterface;
use App\Models\CashAdvance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CashAdvanceRepository implements CashAdvanceRepositoryInterface
{
    public function getAllCashAdvances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CashAdvance::with(['employee', 'user', 'budget', 'approvedBy', 'releasedBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('ca_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('ca_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('ca_date', 'desc')->paginate($perPage);
    }

    public function getCashAdvanceById(int $id): ?CashAdvance
    {
        return CashAdvance::with(['employee', 'user', 'budget', 'approvedBy', 'releasedBy', 'liquidations'])->find($id);
    }

    public function createCashAdvance(array $data): CashAdvance
    {
        $data['unliquidated_balance'] = $data['amount'];
        return CashAdvance::create($data);
    }

    public function updateCashAdvance(int $id, array $data): CashAdvance
    {
        $ca = CashAdvance::findOrFail($id);
        $ca->update($data);
        return $ca->fresh();
    }

    public function deleteCashAdvance(int $id): bool
    {
        $ca = CashAdvance::findOrFail($id);
        return $ca->delete();
    }

    public function approveCashAdvance(int $id, int $approvedBy): CashAdvance
    {
        $ca = CashAdvance::findOrFail($id);
        $ca->update([
            'status' => 'Approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $ca->fresh();
    }

    public function releaseCashAdvance(int $id, int $releasedBy): CashAdvance
    {
        $ca = CashAdvance::findOrFail($id);
        $ca->update([
            'status' => 'Released',
            'released_by' => $releasedBy,
            'released_at' => now(),
        ]);
        return $ca->fresh();
    }

    public function getOverdueCashAdvances(int $perPage = 15): LengthAwarePaginator
    {
        return CashAdvance::with(['employee', 'user'])
            ->where('status', 'Released')
            ->where('due_date_liquidation', '<', now()->toDateString())
            ->orderBy('due_date_liquidation')
            ->paginate($perPage);
    }

    public function getPendingCashAdvances(int $perPage = 15): LengthAwarePaginator
    {
        return CashAdvance::with(['employee', 'user'])
            ->where('status', 'Pending')
            ->orderBy('ca_date', 'desc')
            ->paginate($perPage);
    }

    public function getByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return CashAdvance::with('liquidations')
            ->where('employee_id', $employeeId)
            ->orderBy('ca_date', 'desc')
            ->paginate($perPage);
    }

    public function getCashAdvanceStatistics(): array
    {
        return [
            'total_cash_advances' => CashAdvance::count(),
            'pending' => CashAdvance::where('status', 'Pending')->count(),
            'released' => CashAdvance::where('status', 'Released')->count(),
            'fully_liquidated' => CashAdvance::where('status', 'Fully Liquidated')->count(),
            'overdue' => CashAdvance::where('status', 'Released')
                ->where('due_date_liquidation', '<', now()->toDateString())
                ->count(),
            'total_amount_advanced' => CashAdvance::where('status', '!=', 'Cancelled')->sum('amount'),
            'total_unliquidated' => CashAdvance::whereIn('status', ['Released', 'Partially Liquidated'])->sum('unliquidated_balance'),
        ];
    }
}
