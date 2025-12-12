<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            ServiceRecordSeeder::class,
            LeaveRequestSeeder::class,
            InventoryItemSeeder::class,
            IssuanceSeeder::class,
            BudgetSeeder::class,
            ExpenseSeeder::class,
            SupplierSeeder::class,
            ProcurementSeeder::class,
            PurchaseOrderSeeder::class,
            TrainingSeeder::class,
        ]);
    }
}
