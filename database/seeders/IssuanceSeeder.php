<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Issuance;
use App\Models\InventoryItem;
use App\Models\Employee;
use Carbon\Carbon;

class IssuanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $schoolHead = Employee::where('position', 'Principal IV')->first();
        $adminOfficer = Employee::where('position', 'Administrative Officer IV')->first();

        // Get issued inventory items
        $printer = InventoryItem::where('item_name', 'Epson L3110 Printer')->first();
        $desk = InventoryItem::where('item_name', 'Office Table - Executive')->first();
        $laptop = InventoryItem::where('item_name', 'HP Laptop 14-inch')->first();
        $canonPrinter = InventoryItem::where('item_name', 'Canon ImageClass MF244dw')->first();

        $issuances = [
            [
                'inventory_item_id' => $printer->id,
                'issued_to_employee_id' => $adminOfficer->id,
                'issuance_number' => 'IS-2024-0001',
                'issued_date' => Carbon::now()->subMonths(6),
                'expected_return_date' => null,
                'purpose' => 'Official Use',
                'purpose_details' => 'For administrative office printing needs',
                'custodianship_type' => 'Permanent',
                'status' => 'Active',
                'issued_by' => $schoolHead->id,
                'approved_by' => $schoolHead->id,
                'acknowledged_at' => Carbon::now()->subMonths(6)->addDay(),
            ],
            [
                'inventory_item_id' => $desk->id,
                'issued_to_employee_id' => $schoolHead->id,
                'issuance_number' => 'IS-2024-0002',
                'issued_date' => Carbon::now()->subYear(),
                'expected_return_date' => null,
                'purpose' => 'Official Use',
                'purpose_details' => 'Principal office furniture',
                'custodianship_type' => 'Permanent',
                'status' => 'Active',
                'issued_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'acknowledged_at' => Carbon::now()->subYear()->addDay(),
            ],
            [
                'inventory_item_id' => $laptop->id,
                'issued_to_employee_id' => $employees->where('position', 'Master Teacher I')->first()->id,
                'issuance_number' => 'IS-2024-0003',
                'issued_date' => Carbon::now()->subMonths(3),
                'expected_return_date' => null,
                'purpose' => 'Official Use',
                'purpose_details' => 'For creating learning modules and online teaching',
                'custodianship_type' => 'Permanent',
                'status' => 'Active',
                'issued_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'acknowledged_at' => Carbon::now()->subMonths(3)->addDay(),
            ],
            [
                'inventory_item_id' => $canonPrinter->id,
                'issued_to_employee_id' => $employees->where('position', 'Teacher I')->first()->id,
                'issuance_number' => 'IS-2024-0004',
                'issued_date' => Carbon::now()->subMonths(8),
                'expected_return_date' => null,
                'purpose' => 'Official Use',
                'purpose_details' => 'For printing instructional materials',
                'custodianship_type' => 'Shared',
                'status' => 'Active',
                'issued_by' => $adminOfficer->id,
                'approved_by' => $schoolHead->id,
                'acknowledged_at' => Carbon::now()->subMonths(8)->addDay(),
            ],
        ];

        foreach ($issuances as $issuanceData) {
            Issuance::create($issuanceData);
        }

        $this->command->info('Issuances created successfully!');
    }
}
