<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Dashboard - Administrative Officer Command Center
 *
 * APIs for dashboard metrics including expiring budgets, critical stock levels, and step increment alerts.
 */
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get comprehensive dashboard metrics
     *
     * Retrieve all dashboard metrics in a single call including expiring budgets,
     * critical stock items, and employees due for step increment.
     *
     * @queryParam budget_days integer Days to check for expiring budgets. Example: 60
     * @queryParam stock_prediction_days integer Days to predict stockout. Example: 14
     * @queryParam stock_usage_window integer Days to analyze for usage patterns. Example: 30
     * @queryParam step_increment_days integer Days to check for due step increments. Example: 60
     * @queryParam step_increment_months integer Months required for step increment eligibility. Example: 36
     *
     * @response 200 {
     *   "data": {
     *     "alerts": {
     *       "expiring_budgets": [],
     *       "critical_stock_items": [],
     *       "step_increments_due": []
     *     },
     *     "summary": {
     *       "total_alerts": 15,
     *       "expiring_budgets_count": 3,
     *       "critical_stock_items_count": 7,
     *       "step_increments_due_count": 5,
     *       "high_priority_count": 4
     *     },
     *     "high_priority_alerts": [],
     *     "generated_at": "2026-02-06T10:30:00.000000Z"
     *   }
     * }
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            // Extract query parameters for customization
            $options = [
                'budget_days' => (int) $request->input('budget_days', 60),
                'stock_prediction_days' => (int) $request->input('stock_prediction_days', 14),
                'stock_usage_window' => (int) $request->input('stock_usage_window', 30),
                'step_increment_days' => (int) $request->input('step_increment_days', 60),
                'step_increment_months' => (int) $request->input('step_increment_months', 36),
            ];

            $metrics = $this->dashboardService->getDashboardMetrics($options);

            return response()->json(['data' => $metrics]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard metrics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only expiring budget alerts
     *
     * Retrieve budgets that are expiring within the specified number of days.
     *
     * @queryParam within_days integer Days to check for expiring budgets. Defaults to 60. Example: 60
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 5,
     *       "budget_name": "Training Fund",
     *       "fund_source": "MOOE",
     *       "remaining_balance": 15000.00,
     *       "end_date": "2026-04-15",
     *       "days_until_expiry": 45,
     *       "alert_message": "MOOE - Training Fund expires in 45 days. You have ₱15,000.00 remaining."
     *     }
     *   ]
     * }
     */
    public function expiringBudgets(Request $request): JsonResponse
    {
        try {
            $withinDays = (int) $request->input('within_days', 60);
            $budgets = $this->dashboardService->getExpiringBudgets($withinDays);

            return response()->json(['data' => $budgets]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve expiring budgets.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only critical stock items
     *
     * Retrieve inventory items predicted to run out based on usage patterns.
     *
     * @queryParam prediction_days integer Days to predict stockout. Defaults to 14. Example: 14
     * @queryParam usage_window integer Days to analyze for usage patterns. Defaults to 30. Example: 30
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 12,
     *       "item_name": "Bond Paper (A4)",
     *       "unit": "reams",
     *       "current_balance": 25,
     *       "avg_daily_consumption": 3.5,
     *       "days_until_stockout": 7,
     *       "predicted_stockout_date": "2026-02-13",
     *       "alert_message": "Based on last month's usage, your Bond Paper (A4) will run out in 7 days (by Thursday, February 13, 2026)."
     *     }
     *   ]
     * }
     */
    public function criticalStock(Request $request): JsonResponse
    {
        try {
            $predictionDays = (int) $request->input('prediction_days', 14);
            $usageWindow = (int) $request->input('usage_window', 30);

            $items = $this->dashboardService->getCriticalStockItems($predictionDays, $usageWindow);

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve critical stock items.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only employees due for step increment
     *
     * Retrieve permanent employees who are due for step increment within the specified period.
     *
     * @queryParam within_days integer Days to check for due step increments. Defaults to 60. Example: 60
     * @queryParam eligibility_months integer Months required for step increment eligibility. Defaults to 36. Example: 36
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 8,
     *       "employee_number": "2021-0045",
     *       "employee_name": "Maria Juana Santos",
     *       "position": "Teacher III",
     *       "current_step": 3,
     *       "next_step": 4,
     *       "last_increment_date": "2023-02-15",
     *       "months_since_last_increment": 36,
     *       "next_increment_date": "2026-02-15",
     *       "days_until_due": 9,
     *       "alert_message": "Maria Juana Santos's Step Increment (Step 3 → 4) is due in 9 days (February 15, 2026). Prepare NOSA (Notice of Salary Adjustment)."
     *     }
     *   ]
     * }
     */
    public function stepIncrementsDue(Request $request): JsonResponse
    {
        try {
            $withinDays = (int) $request->input('within_days', 60);
            $eligibilityMonths = (int) $request->input('eligibility_months', 36);

            $employees = $this->dashboardService->getStepIncrementsDue($withinDays, $eligibilityMonths);

            return response()->json(['data' => $employees]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve step increments due.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
