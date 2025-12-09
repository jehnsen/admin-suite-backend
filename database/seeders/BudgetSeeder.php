<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\Employee;
use Carbon\Carbon;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolHead = Employee::where('position', 'Principal IV')->first();
        $adminOfficer = Employee::where('position', 'Administrative Officer IV')->first();

        $budgets = [
            [
                'budget_code' => 'MOOE-2024-001',
                'budget_name' => 'Maintenance and Other Operating Expenses',
                'description' => 'General MOOE allocation for school operations',
                'fund_source' => 'MOOE',
                'classification' => 'AIP',
                'fiscal_year' => 2024,
                'quarter' => 'Q1',
                'allocated_amount' => 500000.00,
                'utilized_amount' => 125000.00,
                'remaining_balance' => 375000.00,
                'category' => 'Operating Expenses',
                'sub_category' => 'General Operations',
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => Carbon::create(2024, 3, 31),
                'status' => 'Active',
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::create(2024, 1, 5),
                'managed_by' => $adminOfficer->id,
            ],
            [
                'budget_code' => 'SEF-2024-001',
                'budget_name' => 'Special Education Fund - Infrastructure',
                'description' => 'SEF allocation for classroom repairs and improvements',
                'fund_source' => 'SEF',
                'classification' => 'SIP',
                'fiscal_year' => 2024,
                'quarter' => null,
                'allocated_amount' => 1000000.00,
                'utilized_amount' => 450000.00,
                'remaining_balance' => 550000.00,
                'category' => 'Capital Outlay',
                'sub_category' => 'Infrastructure',
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => Carbon::create(2024, 12, 31),
                'status' => 'Active',
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::create(2024, 1, 10),
                'managed_by' => $adminOfficer->id,
            ],
            [
                'budget_code' => 'MOOE-2024-002',
                'budget_name' => 'Training and Development Fund',
                'description' => 'Professional development and training for teachers',
                'fund_source' => 'MOOE',
                'classification' => 'SIP',
                'fiscal_year' => 2024,
                'quarter' => null,
                'allocated_amount' => 250000.00,
                'utilized_amount' => 85000.00,
                'remaining_balance' => 165000.00,
                'category' => 'Personnel Services',
                'sub_category' => 'Training and Seminars',
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => Carbon::create(2024, 12, 31),
                'status' => 'Active',
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::create(2024, 1, 15),
                'managed_by' => $adminOfficer->id,
            ],
            [
                'budget_code' => 'MOOE-2024-003',
                'budget_name' => 'ICT Equipment and Supplies',
                'description' => 'Purchase of computers, printers, and office equipment',
                'fund_source' => 'MOOE',
                'classification' => 'AIP',
                'fiscal_year' => 2024,
                'quarter' => 'Q2',
                'allocated_amount' => 300000.00,
                'utilized_amount' => 125000.00,
                'remaining_balance' => 175000.00,
                'category' => 'Capital Outlay',
                'sub_category' => 'ICT Equipment',
                'start_date' => Carbon::create(2024, 4, 1),
                'end_date' => Carbon::create(2024, 6, 30),
                'status' => 'Active',
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::create(2024, 4, 5),
                'managed_by' => $adminOfficer->id,
            ],
        ];

        foreach ($budgets as $budgetData) {
            Budget::create($budgetData);
        }

        $this->command->info('Budgets created successfully!');
    }
}
