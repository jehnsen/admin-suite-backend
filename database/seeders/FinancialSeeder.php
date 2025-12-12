<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disbursement;
use App\Models\Liquidation;
use App\Models\LiquidationItem;
use App\Models\CashAdvance;
use App\Models\User;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $users = User::all();
        $employees = Employee::all();

        if ($users->isEmpty() || $employees->isEmpty()) {
            $this->command->info('Cannot run FinancialSeeder. Users or employees table is empty.');
            return;
        }

        // === Create Cash Advances ===
        $cashAdvance1 = CashAdvance::create([
            'ca_number' => 'CA-2025-001',
            'ca_date' => Carbon::now()->subDays(30),
            'employee_id' => $employees->random()->id,
            'user_id' => $users->random()->id,
            'purpose' => 'Travel expenses for attending a seminar on \'New Teaching Methodologies\' in Cebu City.',
            'amount' => 15000.00,
            'fund_source' => 'MOOE',
            'date_needed' => Carbon::now()->subDays(25),
            'due_date_liquidation' => Carbon::now()->addDays(5),
            'approved_by' => $users->random()->id,
            'approved_at' => Carbon::now()->subDays(28),
            'released_by' => $users->random()->id,
            'released_at' => Carbon::now()->subDays(25),
            'status' => 'Released',
        ]);

        $cashAdvance2 = CashAdvance::create([
            'ca_number' => 'CA-2025-002',
            'ca_date' => Carbon::now()->subDays(20),
            'employee_id' => $employees->random()->id,
            'user_id' => $users->random()->id,
            'purpose' => 'Purchase of materials for the Science Fair, including chemicals, lab equipment, and presentation boards.',
            'amount' => 25000.00,
            'fund_source' => 'SEF',
            'date_needed' => Carbon::now()->subDays(18),
            'due_date_liquidation' => Carbon::now()->addDays(10),
            'approved_by' => $users->random()->id,
            'approved_at' => Carbon::now()->subDays(19),
            'released_by' => $users->random()->id,
            'released_at' => Carbon::now()->subDays(18),
            'status' => 'Released',
        ]);

        // === Create Disbursements ===
        Disbursement::create([
            'dv_number' => 'DV-2025-001',
            'dv_date' => Carbon::now()->subDays(15),
            'payee_name' => $cashAdvance1->employee->full_name,
            'payee_address' => $cashAdvance1->employee->address,
            'purpose' => 'Cash advance for seminar attendance.',
            'amount' => 15000.00,
            'cash_advance_id' => $cashAdvance1->id,
            'fund_source' => 'MOOE',
            'payment_mode' => 'Check',
            'check_number' => 'CHK-12345',
            'check_date' => Carbon::now()->subDays(15),
            'bank_name' => 'Land Bank of the Philippines',
            'gross_amount' => 15000.00,
            'net_amount' => 15000.00,
            'certified_by' => $users->random()->id,
            'certified_at' => Carbon::now()->subDays(14),
            'approved_by' => $users->random()->id,
            'approved_at' => Carbon::now()->subDays(13),
            'paid_by' => $users->random()->id,
            'paid_at' => Carbon::now()->subDays(12),
            'status' => 'Paid',
        ]);
        
        $purchaseOrder = PurchaseOrder::with('supplier')->inRandomOrder()->first();
        if ($purchaseOrder && $purchaseOrder->supplier) {
            Disbursement::create([
                'dv_number' => 'DV-2025-002',
                'dv_date' => Carbon::now()->subDays(10),
                'payee_name' => $purchaseOrder->supplier->business_name,
                'payee_address' => $purchaseOrder->supplier->address,
                'purchase_order_id' => $purchaseOrder->id,
                'purpose' => 'Payment for procurement of office supplies as per PO #' . $purchaseOrder->po_number,
                'amount' => $purchaseOrder->total_amount,
                'fund_source' => 'MOOE',
                'payment_mode' => 'Check',
                'check_number' => 'CHK-12346',
                'check_date' => Carbon::now()->subDays(10),
                'bank_name' => 'Development Bank of the Philippines',
                'gross_amount' => $purchaseOrder->total_amount,
                'tax_withheld' => $purchaseOrder->total_amount * 0.05, // Example 5% tax
                'net_amount' => $purchaseOrder->total_amount * 0.95,
                'certified_by' => $users->random()->id,
                'certified_at' => Carbon::now()->subDays(9),
                'approved_by' => $users->random()->id,
                'approved_at' => Carbon::now()->subDays(8),
                'paid_by' => $users->random()->id,
                'paid_at' => Carbon::now()->subDays(7),
                'status' => 'Paid',
            ]);
        }


        // === Create Liquidations and Liquidation Items ===
        $liquidation1 = Liquidation::create([
            'liquidation_number' => 'LQ-2025-001',
            'liquidation_date' => Carbon::now()->subDays(5),
            'cash_advance_id' => $cashAdvance1->id,
            'cash_advance_amount' => $cashAdvance1->amount,
            'status' => 'Under Review',
            'verified_by' => $users->random()->id,
            'verified_at' => Carbon::now()->subDays(4),
        ]);

        $items1 = [
            ['expense_date' => Carbon::now()->subDays(20), 'particulars' => 'Roundtrip Airfare (MNL-CEB)', 'amount' => 4500.00, 'category' => 'Transportation'],
            ['expense_date' => Carbon::now()->subDays(19), 'particulars' => 'Hotel Accommodation for 3 nights', 'amount' => 6000.00, 'category' => 'Accommodation'],
            ['expense_date' => Carbon::now()->subDays(19), 'particulars' => 'Seminar Registration Fee', 'amount' => 3000.00, 'category' => 'Fees'],
            ['expense_date' => Carbon::now()->subDays(18), 'particulars' => 'Meals for 3 days', 'amount' => 1200.00, 'category' => 'Food'],
        ];

        $totalExpenses1 = 0;
        foreach ($items1 as $index => $item) {
            $li = LiquidationItem::create(array_merge($item, [
                'liquidation_id' => $liquidation1->id,
                'item_number' => $index + 1,
                'or_invoice_number' => 'OR' . $faker->unique()->numberBetween(1000, 5000),
            ]));
            $totalExpenses1 += $li->amount;
        }

        $liquidation1->total_expenses = $totalExpenses1;
        $liquidation1->amount_to_refund = $liquidation1->cash_advance_amount - $totalExpenses1;
        $liquidation1->save();
        
        $cashAdvance1->liquidated_amount = $totalExpenses1;
        $cashAdvance1->unliquidated_balance = $cashAdvance1->amount - $totalExpenses1;
        $cashAdvance1->status = 'Fully Liquidated';
        $cashAdvance1->save();
    }
}
