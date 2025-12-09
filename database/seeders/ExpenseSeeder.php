<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Budget;
use App\Models\Employee;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolHead = Employee::where('position', 'Principal IV')->first();
        $adminOfficer = Employee::where('position', 'Administrative Officer IV')->first();

        $mooeBudget = Budget::where('budget_code', 'MOOE-2024-001')->first();
        $sefBudget = Budget::where('budget_code', 'SEF-2024-001')->first();
        $trainingBudget = Budget::where('budget_code', 'MOOE-2024-002')->first();
        $ictBudget = Budget::where('budget_code', 'MOOE-2024-003')->first();

        $expenses = [
            [
                'budget_id' => $mooeBudget->id,
                'expense_number' => 'EXP-2024-0001',
                'expense_name' => 'Office Supplies Purchase',
                'description' => 'Purchase of bond paper, pens, folders, and other supplies',
                'expense_date' => Carbon::now()->subMonths(2),
                'amount' => 25000.00,
                'payment_method' => 'Check',
                'payee' => 'National Book Store',
                'reference_number' => 'CHK-2024-001',
                'invoice_number' => 'INV-NBS-2024-001',
                'po_number' => 'PO-2024-005',
                'category' => 'Supplies',
                'sub_category' => 'Office Supplies',
                'purpose' => 'Replenishment of office supplies for administrative use',
                'project_name' => null,
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subMonths(2)->addDays(2),
                'disbursed_by' => $adminOfficer->id,
                'disbursed_at' => Carbon::now()->subMonths(2)->addDays(5),
                'status' => 'Disbursed',
                'requires_liquidation' => false,
                'liquidation_status' => 'Not Required',
            ],
            [
                'budget_id' => $sefBudget->id,
                'expense_number' => 'EXP-2024-0002',
                'expense_name' => 'Classroom Repair - Building A',
                'description' => 'Repair of ceiling, painting, and electrical work',
                'expense_date' => Carbon::now()->subMonths(3),
                'amount' => 150000.00,
                'payment_method' => 'Bank Transfer',
                'payee' => 'ABC Construction Services',
                'reference_number' => 'TRF-2024-001',
                'invoice_number' => 'INV-ABC-2024-001',
                'po_number' => 'PO-2024-001',
                'category' => 'Services',
                'sub_category' => 'Repairs and Maintenance',
                'purpose' => 'Repair and improvement of classrooms in Building A',
                'project_name' => 'Classroom Improvement Project 2024',
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subMonths(3)->addDays(3),
                'disbursed_by' => $adminOfficer->id,
                'disbursed_at' => Carbon::now()->subMonths(3)->addWeek(),
                'status' => 'Disbursed',
                'requires_liquidation' => false,
                'liquidation_status' => 'Not Required',
            ],
            [
                'budget_id' => $trainingBudget->id,
                'expense_number' => 'EXP-2024-0003',
                'expense_name' => 'Teachers Training Seminar',
                'description' => 'Professional development seminar on 21st century teaching methods',
                'expense_date' => Carbon::now()->subMonth(),
                'amount' => 45000.00,
                'payment_method' => 'Cash',
                'payee' => 'DepEd Division Training Center',
                'reference_number' => 'CASH-2024-001',
                'invoice_number' => null,
                'po_number' => null,
                'category' => 'Services',
                'sub_category' => 'Training',
                'purpose' => 'Professional development training for 15 teachers',
                'project_name' => 'Teacher Capability Building 2024',
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subMonth()->addDays(2),
                'disbursed_by' => $adminOfficer->id,
                'disbursed_at' => Carbon::now()->subMonth()->addDays(5),
                'status' => 'Disbursed',
                'requires_liquidation' => true,
                'liquidation_deadline' => Carbon::now()->subMonth()->addDays(20),
                'liquidated_at' => Carbon::now()->subMonth()->addDays(18),
                'liquidation_status' => 'Completed',
            ],
            [
                'budget_id' => $ictBudget->id,
                'expense_number' => 'EXP-2024-0004',
                'expense_name' => 'HP Laptop Purchase',
                'description' => 'Purchase of laptop for Master Teacher',
                'expense_date' => Carbon::now()->subMonths(3),
                'amount' => 28000.00,
                'payment_method' => 'Check',
                'payee' => 'Silicon Valley Computer',
                'reference_number' => 'CHK-2024-005',
                'invoice_number' => 'INV-2024-025',
                'po_number' => 'PO-2024-010',
                'category' => 'Equipment',
                'sub_category' => 'ICT Equipment',
                'purpose' => 'Laptop for creating digital learning modules',
                'project_name' => null,
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subMonths(3)->addDays(2),
                'disbursed_by' => $adminOfficer->id,
                'disbursed_at' => Carbon::now()->subMonths(3)->addDays(7),
                'status' => 'Disbursed',
                'requires_liquidation' => false,
                'liquidation_status' => 'Not Required',
            ],
            [
                'budget_id' => $mooeBudget->id,
                'expense_number' => 'EXP-2024-0005',
                'expense_name' => 'Water and Electricity Bills',
                'description' => 'Utility bills for the month of November 2024',
                'expense_date' => Carbon::now()->subWeeks(2),
                'amount' => 18500.00,
                'payment_method' => 'Bank Transfer',
                'payee' => 'Manila Water / Meralco',
                'reference_number' => 'TRF-2024-008',
                'invoice_number' => 'UTIL-NOV-2024',
                'po_number' => null,
                'category' => 'Utilities',
                'sub_category' => 'Water and Electricity',
                'purpose' => 'Payment of monthly utility bills',
                'project_name' => null,
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subWeeks(2)->addDays(1),
                'disbursed_by' => $adminOfficer->id,
                'disbursed_at' => Carbon::now()->subWeeks(2)->addDays(3),
                'status' => 'Disbursed',
                'requires_liquidation' => false,
                'liquidation_status' => 'Not Required',
            ],
            [
                'budget_id' => $trainingBudget->id,
                'expense_number' => 'EXP-2024-0006',
                'expense_name' => 'Leadership Training Budget',
                'description' => 'Upcoming leadership training for school heads',
                'expense_date' => Carbon::now()->addWeeks(2),
                'amount' => 35000.00,
                'payment_method' => 'Check',
                'payee' => 'Leadership Excellence Institute',
                'reference_number' => null,
                'invoice_number' => null,
                'po_number' => 'PO-2024-020',
                'category' => 'Services',
                'sub_category' => 'Training',
                'purpose' => 'Leadership and management training',
                'project_name' => 'School Leaders Development Program',
                'requested_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subDays(5),
                'disbursed_by' => null,
                'disbursed_at' => null,
                'status' => 'Approved',
                'requires_liquidation' => true,
                'liquidation_deadline' => Carbon::now()->addMonth(),
                'liquidation_status' => 'Pending',
            ],
        ];

        foreach ($expenses as $expenseData) {
            Expense::create($expenseData);
        }

        $this->command->info('Expenses created successfully!');
    }
}
