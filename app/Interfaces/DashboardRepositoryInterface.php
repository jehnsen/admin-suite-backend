<?php

namespace App\Interfaces;

interface DashboardRepositoryInterface
{
    /**
     * Get budgets expiring within specified days
     *
     * @param int $withinDays Number of days to check for expiring budgets
     * @return array Array of budgets with expiration details
     */
    public function getExpiringBudgets(int $withinDays = 60): array;

    /**
     * Get items with critical stock levels based on usage prediction
     *
     * @param int $predictionDays Number of days to predict stockout
     * @param int $usageWindowDays Days to analyze for average consumption
     * @return array Array of items predicted to run out
     */
    public function getCriticalStockItems(int $predictionDays = 14, int $usageWindowDays = 30): array;

    /**
     * Get employees due for step increment within specified days
     *
     * @param int $withinDays Number of days to check for due increments
     * @param int $eligibilityMonths Months required for step increment (36 for DepEd)
     * @return array Array of employees due for step increment
     */
    public function getEmployeesDueForStepIncrement(int $withinDays = 60, int $eligibilityMonths = 36): array;

    /**
     * Get comprehensive dashboard metrics
     *
     * @return array Combined dashboard metrics with all alerts
     */
    public function getDashboardMetrics(): array;
}
