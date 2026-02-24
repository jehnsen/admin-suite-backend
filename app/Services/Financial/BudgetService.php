<?php

namespace App\Services\Financial;

use App\Interfaces\Financial\BudgetRepositoryInterface;
use App\Models\Budget;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetService
{
    protected $budgetRepository;

    public function __construct(BudgetRepositoryInterface $budgetRepository)
    {
        $this->budgetRepository = $budgetRepository;
    }

    public function getAllBudgets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->budgetRepository->getAllBudgets($filters, $perPage);
    }

    public function getBudgetById(int $id): ?Budget
    {
        return $this->budgetRepository->getBudgetById($id);
    }

    public function createBudget(array $data): Budget
    {
        // Generate budget code if not provided
        if (empty($data['budget_code'])) {
            $data['budget_code'] = $this->generateBudgetCode($data['fund_source'] ?? 'GEN', $data['fiscal_year'] ?? date('Y'));
        }

        // Set default budget name if not provided
        if (empty($data['budget_name'])) {
            $fundSource = $data['fund_source'] ?? 'General';
            $fiscalYear = $data['fiscal_year'] ?? date('Y');
            $data['budget_name'] = "{$fundSource} Budget Allocation - FY {$fiscalYear}";
        }

        // Set default values
        $data['utilized_amount'] = $data['utilized_amount'] ?? 0;
        $data['status'] = $data['status'] ?? 'Active';
        $data['classification'] = $data['classification'] ?? 'AIP';

        // Set date range to fiscal year if not provided
        if (empty($data['start_date'])) {
            $fiscalYear = $data['fiscal_year'] ?? date('Y');
            $data['start_date'] = "{$fiscalYear}-01-01";
        }
        if (empty($data['end_date'])) {
            $fiscalYear = $data['fiscal_year'] ?? date('Y');
            $data['end_date'] = "{$fiscalYear}-12-31";
        }

        // Auto-calculate remaining balance if not provided
        if (isset($data['allocated_amount']) && isset($data['utilized_amount'])) {
            $data['remaining_balance'] = $data['allocated_amount'] - $data['utilized_amount'];
        }

        return $this->budgetRepository->createBudget($data);
    }

    /**
     * Generate unique budget code
     */
    private function generateBudgetCode(string $fundSource, int $year): string
    {
        $prefix = strtoupper(substr($fundSource, 0, 4));
        $lastBudget = Budget::withTrashed()
            ->where('budget_code', 'like', "{$prefix}-{$year}-%")
            ->orderBy('budget_code', 'desc')
            ->first();

        if ($lastBudget) {
            $lastNumber = (int) substr($lastBudget->budget_code, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}-{$year}-{$newNumber}";
    }

    public function updateBudget(int $id, array $data): Budget
    {
        return $this->budgetRepository->updateBudget($id, $data);
    }

    public function deleteBudget(int $id): bool
    {
        return $this->budgetRepository->deleteBudget($id);
    }

    public function approveBudget(int $id, int $approvedById): Budget
    {
        $data = [
            'status' => 'Approved',
            'approved_by' => $approvedById,
            'approved_at' => now(),
        ];

        return $this->budgetRepository->updateBudget($id, $data);
    }

    public function activateBudget(int $id): Budget
    {
        $data = ['status' => 'Active'];
        return $this->budgetRepository->updateBudget($id, $data);
    }

    public function closeBudget(int $id): Budget
    {
        $data = ['status' => 'Closed'];
        return $this->budgetRepository->updateBudget($id, $data);
    }

    public function getActiveBudgets(int $perPage = 15): LengthAwarePaginator
    {
        return $this->budgetRepository->getActiveBudgets($perPage);
    }

    public function getBudgetsByFiscalYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->budgetRepository->getBudgetsByFiscalYear($year, $perPage);
    }

    public function getBudgetsByFundSource(string $fundSource, int $perPage = 15): LengthAwarePaginator
    {
        return $this->budgetRepository->getBudgetsByFundSource($fundSource, $perPage);
    }

    public function getBudgetUtilization(): array
    {
        return $this->budgetRepository->getBudgetUtilization();
    }

    public function getNearlyDepletedBudgets(): array
    {
        return $this->budgetRepository->getNearlyDepletedBudgets();
    }

    public function getBudgetStatistics(): array
    {
        $utilization = $this->budgetRepository->getBudgetUtilization();
        $nearlyDepleted = $this->budgetRepository->getNearlyDepletedBudgets();

        return [
            'utilization_by_source' => $utilization,
            'nearly_depleted_count' => count($nearlyDepleted),
            'nearly_depleted_budgets' => $nearlyDepleted,
        ];
    }

    public function updateBudgetUtilization(int $budgetId): Budget
    {
        $budget = $this->budgetRepository->getBudgetById($budgetId);

        if ($budget) {
            $budget->updateUtilization();
        }

        return $budget;
    }
}
