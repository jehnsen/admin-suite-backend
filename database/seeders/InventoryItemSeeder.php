<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use Carbon\Carbon;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryItems = [
            [
                'item_code' => 'ICT-2024-001',
                'item_name' => 'Epson L3110 Printer',
                'description' => 'All-in-one ink tank printer with print, scan, copy functions',
                'category' => 'ICT Equipment',
                'unit_of_measure' => 'unit',
                'serial_number' => 'EPSON-L3110-20240001',
                'property_number' => 'PROP-2024-0001',
                'model' => 'L3110',
                'brand' => 'Epson',
                'unit_cost' => 7500.00,
                'quantity' => 1,
                'total_cost' => 7500.00,
                'fund_source' => 'MOOE',
                'supplier' => 'Octagon Computer Superstore',
                'date_acquired' => Carbon::now()->subMonths(6),
                'po_number' => 'PO-2024-001',
                'invoice_number' => 'INV-2024-001',
                'condition' => 'Serviceable',
                'status' => 'Issued',
                'estimated_useful_life_years' => 5,
                'depreciation_rate' => 20.00,
            ],
            [
                'item_code' => 'OFC-2024-001',
                'item_name' => 'Bond Paper Sub 20',
                'description' => 'Long bond paper, 500 sheets per ream',
                'category' => 'Office Supplies',
                'unit_of_measure' => 'ream',
                'serial_number' => null,
                'property_number' => null,
                'model' => null,
                'brand' => 'Paperline',
                'unit_cost' => 180.00,
                'quantity' => 50,
                'total_cost' => 9000.00,
                'fund_source' => 'MOOE',
                'supplier' => 'National Book Store',
                'date_acquired' => Carbon::now()->subMonth(),
                'po_number' => 'PO-2024-002',
                'invoice_number' => 'INV-2024-002',
                'condition' => 'Serviceable',
                'status' => 'In Stock',
            ],
            [
                'item_code' => 'FUR-2024-001',
                'item_name' => 'Office Table - Executive',
                'description' => 'Executive office table with drawers, wooden finish',
                'category' => 'Furniture',
                'unit_of_measure' => 'unit',
                'serial_number' => null,
                'property_number' => 'PROP-2024-0002',
                'model' => 'Executive Desk',
                'brand' => 'Mandaue Foam',
                'unit_cost' => 12000.00,
                'quantity' => 1,
                'total_cost' => 12000.00,
                'fund_source' => 'SEF',
                'supplier' => 'Mandaue Foam',
                'date_acquired' => Carbon::now()->subYear(),
                'po_number' => 'PO-2023-045',
                'invoice_number' => 'INV-2023-089',
                'condition' => 'Serviceable',
                'status' => 'Issued',
                'estimated_useful_life_years' => 10,
                'depreciation_rate' => 10.00,
            ],
            [
                'item_code' => 'ICT-2024-002',
                'item_name' => 'HP Laptop 14-inch',
                'description' => 'HP Laptop with Intel Core i5, 8GB RAM, 256GB SSD',
                'category' => 'ICT Equipment',
                'unit_of_measure' => 'unit',
                'serial_number' => 'HP-2024-ABC123',
                'property_number' => 'PROP-2024-0003',
                'model' => 'HP 14s-dq3000',
                'brand' => 'HP',
                'unit_cost' => 28000.00,
                'quantity' => 1,
                'total_cost' => 28000.00,
                'fund_source' => 'DepEd Central',
                'supplier' => 'Silicon Valley Computer',
                'date_acquired' => Carbon::now()->subMonths(3),
                'po_number' => 'PO-2024-010',
                'invoice_number' => 'INV-2024-025',
                'condition' => 'Serviceable',
                'status' => 'Issued',
                'estimated_useful_life_years' => 5,
                'depreciation_rate' => 20.00,
            ],
            [
                'item_code' => 'OFC-2024-002',
                'item_name' => 'Whiteboard Marker - Black',
                'description' => 'Whiteboard marker, permanent ink, black color',
                'category' => 'Office Supplies',
                'unit_of_measure' => 'piece',
                'serial_number' => null,
                'property_number' => null,
                'model' => null,
                'brand' => 'Pilot',
                'unit_cost' => 35.00,
                'quantity' => 100,
                'total_cost' => 3500.00,
                'fund_source' => 'MOOE',
                'supplier' => 'National Book Store',
                'date_acquired' => Carbon::now()->subWeeks(2),
                'po_number' => 'PO-2024-015',
                'invoice_number' => 'INV-2024-030',
                'condition' => 'Serviceable',
                'status' => 'In Stock',
            ],
            [
                'item_code' => 'ICT-2024-003',
                'item_name' => 'Canon ImageClass MF244dw',
                'description' => 'Monochrome laser multifunction printer with WiFi',
                'category' => 'ICT Equipment',
                'unit_of_measure' => 'unit',
                'serial_number' => 'CANON-MF244-2024-001',
                'property_number' => 'PROP-2024-0004',
                'model' => 'ImageClass MF244dw',
                'brand' => 'Canon',
                'unit_cost' => 18500.00,
                'quantity' => 1,
                'total_cost' => 18500.00,
                'fund_source' => 'MOOE',
                'supplier' => 'Canon Philippines',
                'date_acquired' => Carbon::now()->subMonths(8),
                'po_number' => 'PO-2023-098',
                'invoice_number' => 'INV-2023-156',
                'condition' => 'Serviceable',
                'status' => 'Issued',
                'estimated_useful_life_years' => 5,
                'depreciation_rate' => 20.00,
            ],
        ];

        foreach ($inventoryItems as $itemData) {
            InventoryItem::create($itemData);
        }

        $this->command->info('Inventory items created successfully!');
    }
}
