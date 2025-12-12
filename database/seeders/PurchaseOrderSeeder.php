<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure a user exists
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        // Ensure a supplier exists
        // $supplier = Supplier::firstOrCreate(
        //     ['business_name' => 'Test Supplier'],
        //     [
        //         'supplier_code' => 'SUP-001',
        //         'trade_name' => 'Test Supplier Inc.',
        //         'owner_name' => 'John Doe',
        //         'business_type' => 'Corporation',
        //         'email' => 'supplier@example.com',
        //         'phone_number' => '123-456-7890',
        //         'address' => '123 Supplier St',
        //         'city' => 'Business City',
        //         'tin' => '123-456-789-000',
        //         'status' => 'Active',
        //     ]
        // );

        // Ensure a purchase request exists
        $purchaseRequest = PurchaseRequest::firstOrCreate(
            ['pr_number' => 'PR-2025-001'],
            [
                'pr_date' => Carbon::now(),
                'requested_by' => $user->id,
                'department' => 'Admin',
                'purpose' => 'Office Supplies',
                'status' => 'Approved',
                'total_amount' => 1000.00,
            ]
        );


        // Create a Purchase Order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-2025-001',
            'po_date' => Carbon::now(),
            'purchase_request_id' => $purchaseRequest->id,
            'quotation_id' => null,
            'supplier_id' => 1,
            'subtotal' => 1000.00,
            'tax_amount' => 120.00,
            'discount_amount' => 50.00,
            'shipping_cost' => 100.00,
            'total_amount' => 1170.00,
            'fund_source' => 'MOOE',
            'delivery_location' => 'Main Office',
            'delivery_date' => Carbon::now()->addDays(14),
            'delivery_terms' => 'FOB Destination',
            'payment_terms' => 'Net 30',
            'payment_method' => 'Check',
            'status' => 'Pending',
            'prepared_by' => $user->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create Purchase Order Items
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_description' => 'Sample Item 1',
            'unit_of_measure' => 'pcs',
            'quantity_ordered' => 10,
            'unit_price' => 50.00,
            'total_price' => 500.00,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_description' => 'Sample Item 2',
            'unit_of_measure' => 'box',
            'quantity_ordered' => 5,
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);
    }
}
