<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;

class ProcurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the admin officer user for creating PRs
        $adminOfficer = User::where('email', 'adminofficer@deped.gov.ph')->first();

        if (!$adminOfficer) {
            echo "Admin Officer user not found. Please run UserSeeder first.\n";
            return;
        }

        // Purchase Request 1 - Office Supplies
        $pr1 = PurchaseRequest::create([
            'pr_number' => 'PR-2025-0001',
            'pr_date' => now()->subDays(10),
            'requested_by' => $adminOfficer->id,
            'department' => 'Administrative Office',
            'section' => 'General Services',
            'purpose' => 'Replenishment of office supplies for Q1 2025',
            'fund_source' => 'MOOE',
            'fund_cluster' => 'Fund 101',
            'procurement_mode' => 'Small Value Procurement',
            'estimated_budget' => 25000.00,
            'total_amount' => 24500.00,
            'date_needed' => now()->addDays(15),
            'delivery_location' => 'School Supply Room',
            'status' => 'Approved',
            'approved_by' => 2, // School Head
            'approved_at' => now()->subDays(5),
            'approval_remarks' => 'Approved for immediate procurement',
        ]);

        // Items for PR1
        $pr1Items = [
            [
                'item_code' => 'OS-001',
                'item_description' => 'Bond Paper Short (500 sheets/ream)',
                'unit_of_measure' => 'ream',
                'quantity' => 50,
                'unit_cost' => 150.00,
                'specifications' => 'Sub 20, 70gsm, white',
                'category' => 'Office Supplies',
                'stock_on_hand' => 10,
                'monthly_consumption' => 15,
            ],
            [
                'item_code' => 'OS-002',
                'item_description' => 'Ballpen (black, 0.5mm)',
                'unit_of_measure' => 'box',
                'quantity' => 20,
                'unit_cost' => 120.00,
                'specifications' => '12 pcs per box',
                'category' => 'Office Supplies',
                'stock_on_hand' => 5,
                'monthly_consumption' => 8,
            ],
            [
                'item_code' => 'OS-003',
                'item_description' => 'Folder Long (plastic)',
                'unit_of_measure' => 'box',
                'quantity' => 15,
                'unit_cost' => 200.00,
                'specifications' => '12 pcs per box, assorted colors',
                'category' => 'Office Supplies',
                'stock_on_hand' => 3,
                'monthly_consumption' => 5,
            ],
            [
                'item_code' => 'OS-004',
                'item_description' => 'Stapler Heavy Duty',
                'unit_of_measure' => 'pcs',
                'quantity' => 5,
                'unit_cost' => 350.00,
                'specifications' => 'Can staple up to 100 sheets',
                'category' => 'Office Supplies',
                'stock_on_hand' => 1,
                'monthly_consumption' => 1,
            ],
            [
                'item_code' => 'OS-005',
                'item_description' => 'Staple Wire #35',
                'unit_of_measure' => 'box',
                'quantity' => 10,
                'unit_cost' => 85.00,
                'specifications' => '1000 pcs per box',
                'category' => 'Office Supplies',
                'stock_on_hand' => 2,
                'monthly_consumption' => 3,
            ],
        ];

        foreach ($pr1Items as $index => $item) {
            $item['purchase_request_id'] = $pr1->id;
            $item['item_number'] = $index + 1;
            $item['total_cost'] = $item['quantity'] * $item['unit_cost'];
            PurchaseRequestItem::create($item);
        }

        // Purchase Request 2 - IT Equipment
        $pr2 = PurchaseRequest::create([
            'pr_number' => 'PR-2025-0002',
            'pr_date' => now()->subDays(5),
            'requested_by' => $adminOfficer->id,
            'department' => 'Administrative Office',
            'section' => 'ICT',
            'purpose' => 'Replacement of obsolete computer units and printers',
            'fund_source' => 'SEF',
            'fund_cluster' => 'Fund 102',
            'procurement_mode' => 'Shopping',
            'estimated_budget' => 150000.00,
            'total_amount' => 148000.00,
            'date_needed' => now()->addDays(30),
            'delivery_location' => 'Computer Laboratory',
            'status' => 'For Quotation',
            'approved_by' => 2, // School Head
            'approved_at' => now()->subDays(2),
            'approval_remarks' => 'Approved. Proceed with canvassing.',
        ]);

        // Items for PR2
        $pr2Items = [
            [
                'item_code' => 'IT-001',
                'item_description' => 'Desktop Computer',
                'unit_of_measure' => 'unit',
                'quantity' => 5,
                'unit_cost' => 25000.00,
                'specifications' => 'Intel Core i5, 8GB RAM, 256GB SSD, 21.5" Monitor, Windows 11',
                'category' => 'IT Equipment',
            ],
            [
                'item_code' => 'IT-002',
                'item_description' => 'Laser Printer (Network)',
                'unit_of_measure' => 'unit',
                'quantity' => 2,
                'unit_cost' => 11500.00,
                'specifications' => 'A4, Monochrome, Network capable, Duplex printing',
                'category' => 'IT Equipment',
            ],
        ];

        foreach ($pr2Items as $index => $item) {
            $item['purchase_request_id'] = $pr2->id;
            $item['item_number'] = $index + 1;
            $item['total_cost'] = $item['quantity'] * $item['unit_cost'];
            PurchaseRequestItem::create($item);
        }

        // Purchase Request 3 - Pending
        $pr3 = PurchaseRequest::create([
            'pr_number' => 'PR-2025-0003',
            'pr_date' => now()->subDays(2),
            'requested_by' => $adminOfficer->id,
            'department' => 'Administrative Office',
            'section' => 'General Services',
            'purpose' => 'Procurement of furniture for new faculty room',
            'fund_source' => 'MOOE',
            'fund_cluster' => 'Fund 101',
            'procurement_mode' => 'Small Value Procurement',
            'estimated_budget' => 45000.00,
            'total_amount' => 44500.00,
            'date_needed' => now()->addDays(45),
            'delivery_location' => 'New Faculty Room, 2nd Floor',
            'status' => 'Pending',
        ]);

        // Items for PR3
        $pr3Items = [
            [
                'item_code' => 'FRN-001',
                'item_description' => 'Office Table (executive)',
                'unit_of_measure' => 'unit',
                'quantity' => 3,
                'unit_cost' => 5500.00,
                'specifications' => '1.5m x 0.75m, wood finish, with drawers',
                'category' => 'Furniture',
            ],
            [
                'item_code' => 'FRN-002',
                'item_description' => 'Office Chair (executive)',
                'unit_of_measure' => 'unit',
                'quantity' => 3,
                'unit_cost' => 3500.00,
                'specifications' => 'High back, swivel, adjustable height, black leather',
                'category' => 'Furniture',
            ],
            [
                'item_code' => 'FRN-003',
                'item_description' => 'Filing Cabinet (4 drawers)',
                'unit_of_measure' => 'unit',
                'quantity' => 2,
                'unit_cost' => 6500.00,
                'specifications' => 'Steel, 4 drawers, with lock, legal size',
                'category' => 'Furniture',
            ],
        ];

        foreach ($pr3Items as $index => $item) {
            $item['purchase_request_id'] = $pr3->id;
            $item['item_number'] = $index + 1;
            $item['total_cost'] = $item['quantity'] * $item['unit_cost'];
            PurchaseRequestItem::create($item);
        }
    }
}
