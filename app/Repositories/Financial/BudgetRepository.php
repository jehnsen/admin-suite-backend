<?php

namespace App\Repositories\Financial;

use App\Interfaces\Financial\BudgetRepositoryInterface;
use App\Models\Budget;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetRepository implements BudgetRepositoryInterface
{
    public function getAllBudgets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Budget::with(['approvedByEmployee', 'managedByEmployee', 'expenses']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by fund source
        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        // Filter by classification
        if (!empty($filters['classification'])) {
            $query->where('classification', $filters['classification']);
        }

        // Filter by fiscal year
        if (!empty($filters['fiscal_year'])) {
            $query->where('fiscal_year', $filters['fiscal_year']);
        }

        // Filter by quarter
        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
        }

        // Search by budget code or name
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('budget_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('budget_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getBudgetById(int $id): ?Budget
    {
        return Budget::with(['approvedByEmployee', 'managedByEmployee', 'expenses'])->find($id);
    }

    public function createBudget(array $data): Budget
    {
        return Budget::create($data);
    }

    public function updateBudget(int $id, array $data): Budget
    {
        $budget = Budget::findOrFail($id);
        $budget->update($data);
        return $budget->fresh();
    }

    public function deleteBudget(int $id): bool
    {
        $budget = Budget::findOrFail($id);
        return $budget->delete();
    }

    public function getActiveBudgets(int $perPage = 15): LengthAwarePaginator
    {
        return Budget::with(['approvedByEmployee', 'managedByEmployee'])
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getBudgetsByFiscalYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return Budget::with(['approvedByEmployee', 'managedByEmployee'])
            ->where('fiscal_year', $year)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getBudgetsByFundSource(string $fundSource, int $perPage = 15): LengthAwarePaginator
    {
        return Budget::with(['approvedByEmployee', 'managedByEmployee'])
            ->where('fund_source', $fundSource)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getBudgetUtilization(): array
    {
        return Budget::select([
            'fund_source',
            DB::raw('CAST(SUM(allocated_amount) AS DECIMAL(15,2)) as total_allocated'),
            DB::raw('CAST(SUM(utilized_amount) AS DECIMAL(15,2)) as total_utilized'),
            DB::raw('CAST(SUM(remaining_balance) AS DECIMAL(15,2)) as total_remaining'),
            DB::raw('ROUND((SUM(utilized_amount) / NULLIF(SUM(allocated_amount), 0) * 100), 2) as utilization_percentage')
        ])
        ->where('status', 'Active')
        ->groupBy('fund_source')
        ->get()
        ->toArray();
    }

    public function getNearlyDepletedBudgets(): array
    {
        return Budget::where('status', 'Active')
            ->whereRaw('(utilized_amount / allocated_amount * 100) >= 90')
            ->with(['approvedByEmployee', 'managedByEmployee'])
            ->get()
            ->toArray();
    }
}
