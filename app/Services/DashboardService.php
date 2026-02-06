<?php

namespace App\Services;

use App\Interfaces\DashboardRepositoryInterface;

class DashboardService
{
    protected $dashboardRepository;

    public function __construct(DashboardRepositoryInterface $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    /**
     * Get all dashboard metrics with configurable thresholds
     *
     * @param array $options Configuration options for metric thresholds
     * @return array Complete dashboard metrics with alerts and summaries
     */
    public function getDashboardMetrics(array $options = []): array
    {
        // Extract options with defaults
        $budgetDays = $options['budget_days'] ?? 60;
        $stockPredictionDays = $options['stock_prediction_days'] ?? 14;
        $stockUsageWindow = $options['stock_usage_window'] ?? 30;
        $stepIncrementDays = $options['step_increment_days'] ?? 60;
        $stepIncrementMonths = $options['step_increment_months'] ?? 36;

        // Get individual metrics
        $expiringBudgets = $this->dashboardRepository->getExpiringBudgets($budgetDays);
        $criticalStockItems = $this->dashboardRepository->getCriticalStockItems(
            $stockPredictionDays,
            $stockUsageWindow
        );
        $stepIncrementsDue = $this->dashboardRepository->getEmployeesDueForStepIncrement(
            $stepIncrementDays,
            $stepIncrementMonths
        );

        // Calculate alert priorities
        $highPriorityAlerts = $this->calculateHighPriorityAlerts(
            $expiringBudgets,
            $criticalStockItems,
            $stepIncrementsDue
        );

        return [
            'alerts' => [
                'expiring_budgets' => $expiringBudgets,
                'critical_stock_items' => $criticalStockItems,
                'step_increments_due' => $stepIncrementsDue,
            ],
            'summary' => [
                'total_alerts' => count($expiringBudgets) + count($criticalStockItems) + count($stepIncrementsDue),
                'expiring_budgets_count' => count($expiringBudgets),
                'critical_stock_items_count' => count($criticalStockItems),
                'step_increments_due_count' => count($stepIncrementsDue),
                'high_priority_count' => count($highPriorityAlerts),
            ],
            'high_priority_alerts' => $highPriorityAlerts,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get only expiring budgets
     *
     * @param int $withinDays Number of days to check for expiring budgets
     * @return array Array of expiring budgets
     */
    public function getExpiringBudgets(int $withinDays = 60): array
    {
        return $this->dashboardRepository->getExpiringBudgets($withinDays);
    }

    /**
     * Get only critical stock items
     *
     * @param int $predictionDays Number of days to predict stockout
     * @param int $usageWindow Number of days to analyze for usage patterns
     * @return array Array of critical stock items
     */
    public function getCriticalStockItems(int $predictionDays = 14, int $usageWindow = 30): array
    {
        return $this->dashboardRepository->getCriticalStockItems($predictionDays, $usageWindow);
    }

    /**
     * Get only employees due for step increment
     *
     * @param int $withinDays Number of days to check for due increments
     * @param int $eligibilityMonths Months required for step increment eligibility
     * @return array Array of employees due for step increment
     */
    public function getStepIncrementsDue(int $withinDays = 60, int $eligibilityMonths = 36): array
    {
        return $this->dashboardRepository->getEmployeesDueForStepIncrement($withinDays, $eligibilityMonths);
    }

    /**
     * Calculate high priority alerts (items due within 7 days)
     *
     * @param array $budgets Array of expiring budgets
     * @param array $stock Array of critical stock items
     * @param array $stepIncrements Array of employees due for step increment
     * @return array Array of high priority alerts sorted by urgency
     */
    private function calculateHighPriorityAlerts(array $budgets, array $stock, array $stepIncrements): array
    {
        $highPriority = [];

        // Budgets expiring within 7 days
        foreach ($budgets as $budget) {
            if ($budget['days_until_expiry'] <= 7) {
                $highPriority[] = [
                    'type' => 'budget_expiring',
                    'priority' => 'high',
                    'days_remaining' => $budget['days_until_expiry'],
                    'message' => $budget['alert_message'],
                    'data' => $budget
                ];
            }
        }

        // Stock running out within 7 days
        foreach ($stock as $item) {
            if ($item['days_until_stockout'] <= 7) {
                $highPriority[] = [
                    'type' => 'stock_critical',
                    'priority' => 'high',
                    'days_remaining' => $item['days_until_stockout'],
                    'message' => $item['alert_message'],
                    'data' => $item
                ];
            }
        }

        // Step increments due within 30 days (more urgent)
        foreach ($stepIncrements as $increment) {
            if ($increment['days_until_due'] <= 30) {
                $highPriority[] = [
                    'type' => 'step_increment_due',
                    'priority' => $increment['days_until_due'] <= 7 ? 'high' : 'medium',
                    'days_remaining' => $increment['days_until_due'],
                    'message' => $increment['alert_message'],
                    'data' => $increment
                ];
            }
        }

        // Sort by days remaining (most urgent first)
        usort($highPriority, function($a, $b) {
            return $a['days_remaining'] <=> $b['days_remaining'];
        });

        return $highPriority;
    }
}
