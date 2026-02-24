<?php

namespace App\Repositories;

use App\Interfaces\DashboardRepositoryInterface;
use App\Models\Budget;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    /**
     * Get budgets expiring within specified days
     */
    public function getExpiringBudgets(int $withinDays = 60): array
    {
        return Budget::select([
            'id',
            'budget_name',
            'fund_source',
            'end_date',
            'remaining_balance',
            DB::raw('DATEDIFF(end_date, CURDATE()) as days_until_expiry')
        ])
        ->where('status', 'Active')
        ->whereRaw('DATEDIFF(end_date, CURDATE()) BETWEEN 0 AND ?', [$withinDays])
        ->orderBy('end_date', 'asc')
        ->get()
        ->map(function($budget) {
            return [
                'id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'fund_source' => $budget->fund_source,
                'remaining_balance' => (float) $budget->remaining_balance,
                'end_date' => $budget->end_date->format('Y-m-d'),
                'days_until_expiry' => $budget->days_until_expiry,
                'alert_message' => "{$budget->fund_source} - {$budget->budget_name} expires in {$budget->days_until_expiry} " .
                    ($budget->days_until_expiry == 1 ? 'day' : 'days') .
                    ". You have ₱" . number_format($budget->remaining_balance, 2) . " remaining."
            ];
        })
        ->toArray();
    }

    /**
     * Get items with critical stock levels based on usage prediction
     */
    public function getCriticalStockItems(int $predictionDays = 14, int $usageWindowDays = 30): array
    {
        $cutoffDate = now()->subDays($usageWindowDays)->format('Y-m-d');

        // Complex query to calculate average daily consumption and predict stockout dates
        $results = DB::table('inventory_items as ii')
            ->select([
                'ii.id',
                'ii.item_name',
                'ii.unit_of_measure as unit',
                DB::raw('COALESCE(latest_balance.balance, 0) as current_balance'),
                DB::raw('COALESCE(usage_stats.total_out, 0) as total_consumed'),
                DB::raw('COALESCE(usage_stats.total_out / ' . $usageWindowDays . ', 0) as avg_daily_consumption'),
                DB::raw('CASE
                    WHEN COALESCE(usage_stats.total_out / ' . $usageWindowDays . ', 0) > 0
                    THEN FLOOR(COALESCE(latest_balance.balance, 0) / (usage_stats.total_out / ' . $usageWindowDays . '))
                    ELSE 999
                END as days_until_stockout'),
                DB::raw('DATE_ADD(CURDATE(), INTERVAL CASE
                    WHEN COALESCE(usage_stats.total_out / ' . $usageWindowDays . ', 0) > 0
                    THEN FLOOR(COALESCE(latest_balance.balance, 0) / (usage_stats.total_out / ' . $usageWindowDays . '))
                    ELSE 999
                END DAY) as predicted_stockout_date')
            ])
            // Subquery 1: Get latest stock balance
            ->leftJoin(DB::raw('(
                SELECT sc1.inventory_item_id, sc1.balance
                FROM stock_cards sc1
                INNER JOIN (
                    SELECT inventory_item_id, MAX(created_at) as max_created
                    FROM stock_cards
                    GROUP BY inventory_item_id
                ) sc2 ON sc1.inventory_item_id = sc2.inventory_item_id
                    AND sc1.created_at = sc2.max_created
            ) as latest_balance'), 'ii.id', '=', 'latest_balance.inventory_item_id')
            // Subquery 2: Calculate total consumption from last N days
            ->leftJoin(DB::raw('(
                SELECT inventory_item_id, SUM(quantity_out) as total_out
                FROM stock_cards
                WHERE transaction_type = "Stock Out"
                    AND transaction_date >= "' . $cutoffDate . '"
                GROUP BY inventory_item_id
            ) as usage_stats'), 'ii.id', '=', 'usage_stats.inventory_item_id')
            // Filter: Only items predicted to run out within specified days
            ->whereRaw('CASE
                WHEN COALESCE(usage_stats.total_out / ' . $usageWindowDays . ', 0) > 0
                THEN FLOOR(COALESCE(latest_balance.balance, 0) / (usage_stats.total_out / ' . $usageWindowDays . '))
                ELSE 999
            END <= ?', [$predictionDays])
            // Only items with usage history
            ->whereRaw('COALESCE(usage_stats.total_out, 0) > 0')
            ->orderBy('days_until_stockout', 'asc')
            ->get();

        return $results->map(function($item) {
            $daysText = $item->days_until_stockout == 1 ? 'day' : 'days';
            $stockoutDate = date('l, F j, Y', strtotime($item->predicted_stockout_date));

            return [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'unit' => $item->unit,
                'current_balance' => (float) $item->current_balance,
                'avg_daily_consumption' => round($item->avg_daily_consumption, 2),
                'days_until_stockout' => (int) $item->days_until_stockout,
                'predicted_stockout_date' => $item->predicted_stockout_date,
                'alert_message' => "Based on last month's usage, your {$item->item_name} will run out in {$item->days_until_stockout} {$daysText} (by {$stockoutDate})."
            ];
        })->toArray();
    }

    /**
     * Get employees due for step increment within specified days
     */
    public function getEmployeesDueForStepIncrement(int $withinDays = 60, int $eligibilityMonths = 36): array
    {
        // Get employees with their latest step increment/promotion date
        $results = DB::table('employees as e')
            ->select([
                'e.id',
                'e.employee_number',
                'e.first_name',
                'e.last_name',
                'e.position',
                'e.step_increment as current_step',
                'e.employment_status',
                'e.date_hired',
                DB::raw('COALESCE(latest_step.last_step_date, e.date_hired) as last_step_increment_date'),
                DB::raw('TIMESTAMPDIFF(MONTH, COALESCE(latest_step.last_step_date, e.date_hired), CURDATE()) as months_since_last_increment'),
                DB::raw($eligibilityMonths . ' - TIMESTAMPDIFF(MONTH, COALESCE(latest_step.last_step_date, e.date_hired), CURDATE()) as months_remaining'),
                DB::raw('DATE_ADD(COALESCE(latest_step.last_step_date, e.date_hired), INTERVAL ' . $eligibilityMonths . ' MONTH) as next_increment_date'),
                DB::raw('DATEDIFF(DATE_ADD(COALESCE(latest_step.last_step_date, e.date_hired), INTERVAL ' . $eligibilityMonths . ' MONTH), CURDATE()) as days_until_due')
            ])
            // Subquery: Get latest service record with promotion or new appointment
            ->leftJoin(DB::raw('(
                SELECT sr1.employee_id, sr1.date_from as last_step_date
                FROM service_records sr1
                INNER JOIN (
                    SELECT employee_id, MAX(date_from) as max_date
                    FROM service_records
                    WHERE action_type IN ("Promotion", "New Appointment")
                    GROUP BY employee_id
                ) sr2 ON sr1.employee_id = sr2.employee_id
                    AND sr1.date_from = sr2.max_date
            ) as latest_step'), 'e.id', '=', 'latest_step.employee_id')
            ->where('e.status', 'Active')
            ->where('e.employment_status', 'Permanent')
            ->whereRaw('DATEDIFF(DATE_ADD(COALESCE(latest_step.last_step_date, e.date_hired), INTERVAL ' . $eligibilityMonths . ' MONTH), CURDATE()) BETWEEN 0 AND ?', [$withinDays])
            ->whereNull('e.deleted_at')
            ->orderBy('days_until_due', 'asc')
            ->get();

        return $results->map(function($emp) {
            $fullName = trim("{$emp->first_name} {$emp->last_name}");
            $nextStep = $emp->current_step + 1;
            $daysText = $emp->days_until_due == 1 ? 'day' : 'days';
            $incrementDate = date('F j, Y', strtotime($emp->next_increment_date));

            return [
                'id' => $emp->id,
                'employee_number' => $emp->employee_number,
                'employee_name' => $fullName,
                'position' => $emp->position,
                'current_step' => (int) $emp->current_step,
                'next_step' => $nextStep,
                'last_increment_date' => $emp->last_step_increment_date,
                'months_since_last_increment' => (int) $emp->months_since_last_increment,
                'next_increment_date' => $emp->next_increment_date,
                'days_until_due' => (int) $emp->days_until_due,
                'alert_message' => "{$fullName}'s Step Increment (Step {$emp->current_step} → {$nextStep}) is due in {$emp->days_until_due} {$daysText} ({$incrementDate}). Prepare NOSA (Notice of Salary Adjustment)."
            ];
        })->toArray();
    }

    /**
     * Get comprehensive dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        $expiringBudgets = $this->getExpiringBudgets();
        $criticalStockItems = $this->getCriticalStockItems();
        $stepIncrementsDue = $this->getEmployeesDueForStepIncrement();

        return [
            'expiring_budgets' => $expiringBudgets,
            'critical_stock_items' => $criticalStockItems,
            'employees_due_for_step_increment' => $stepIncrementsDue,
            'summary' => [
                'expiring_budgets_count' => count($expiringBudgets),
                'critical_stock_items_count' => count($criticalStockItems),
                'step_increments_due_count' => count($stepIncrementsDue),
                'total_alerts' => count($expiringBudgets) + count($criticalStockItems) + count($stepIncrementsDue)
            ],
            'generated_at' => now()->toISOString()
        ];
    }
}
