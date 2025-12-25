<?php

namespace App\Interfaces\Financial;

use App\Models\Budget;
use Illuminate\Pagination\LengthAwarePaginator;

interface BudgetRepositoryInterface
{
    public function getAllBudgets(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getBudgetById(int $id): ?Budget;
    public function createBudget(array $data): Budget;
    public function updateBudget(int $id, array $data): Budget;
    public function deleteBudget(int $id): bool;
    public function getActiveBudgets(int $perPage = 15): LengthAwarePaginator;
    public function getBudgetsByFiscalYear(int $year, int $perPage = 15): LengthAwarePaginator;
    public function getBudgetsByFundSource(string $fundSource, int $perPage = 15): LengthAwarePaginator;
    public function getBudgetUtilization(): array;
    public function getNearlyDepletedBudgets(): array;
}
