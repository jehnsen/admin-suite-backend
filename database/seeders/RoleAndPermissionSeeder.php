<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // User Account Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'reset_user_password',
            'manage_user_roles',

            // Employee Management
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'promote_employees',

            // 201 File Access
            'view_201_file',
            'edit_201_file',

            // Leave Management
            'view_leave_requests',
            'create_leave_request',
            'recommend_leave',
            'approve_leave',
            'reject_leave',
            'manage_leave_credits',

            // Service Records
            'view_service_records',
            'create_service_records',
            'edit_service_records',
            'delete_service_records',

            // Inventory Management
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'delete_inventory',
            'issue_inventory',

            // Financial Management
            'view_budget',
            'create_budget',
            'edit_budget',
            'approve_budget',
            'view_expenses',
            'create_expense',
            'approve_expense',

            // Reports
            'view_reports',
            'export_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions

        // 1. Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. School Head - Management level access
        $schoolHead = Role::firstOrCreate(['name' => 'School Head']);
        $schoolHead->syncPermissions([
            'view_users',
            'view_employees',
            'create_employees',
            'edit_employees',
            'view_201_file',
            'view_leave_requests',
            'approve_leave',
            'reject_leave',
            'view_service_records',
            'view_inventory',
            'issue_inventory',
            'view_budget',
            'view_expenses',
            'approve_expense',
            'view_reports',
            'export_reports',
        ]);

        // 3. Admin Officer - System Owner
        $adminOfficer = Role::firstOrCreate(['name' => 'Admin Officer']);
        $adminOfficer->syncPermissions([
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'reset_user_password',
            'manage_user_roles',
            'view_employees',
            'create_employees',
            'edit_employees',
            'view_201_file',
            'edit_201_file',
            'view_leave_requests',
            'recommend_leave',
            'manage_leave_credits',
            'view_service_records',
            'create_service_records',
            'edit_service_records',
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'issue_inventory',
            'view_budget',
            'create_budget',
            'edit_budget',
            'view_expenses',
            'create_expense',
            'view_reports',
            'export_reports',
        ]);

        // 4. Teacher/Staff - Limited access
        $teacherStaff = Role::firstOrCreate(['name' => 'Teacher/Staff']);
        $teacherStaff->syncPermissions([
            'view_employees', // Can view colleagues
            'create_leave_request',
            'view_leave_requests', // Own only
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
