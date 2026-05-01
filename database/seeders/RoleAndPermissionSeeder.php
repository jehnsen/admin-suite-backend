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
            'manage_user_permissions',

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

            // Attendance/DTR Management
            'view_attendance',
            'create_attendance',
            'edit_attendance',
            'delete_attendance',
            'approve_attendance',
            'manage_attendance_settings',
            'export_attendance',
            'upload_attendance_csv',

            // Service Credits Management
            'view_service_credits',
            'create_service_credits',
            'edit_service_credits',
            'delete_service_credits',
            'approve_service_credits',
            'apply_service_credit_offset',
            'manage_service_credit_rules',

            // Inventory Management
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'delete_inventory',
            'issue_inventory',
            'approve_inventory_adjustments',

            // Financial Management
            'view_budget',
            'create_budget',
            'edit_budget',
            'delete_budget',
            'approve_budget',
            'view_expenses',
            'create_expense',
            'approve_expense',

            // Supplier Management
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',

            // Purchase Request Management
            'view_purchase_requests',
            'create_purchase_requests',
            'edit_purchase_requests',
            'delete_purchase_requests',
            'submit_purchase_request',
            'recommend_purchase_request',
            'approve_purchase_request',

            // Quotation Management
            'view_quotations',
            'create_quotations',
            'edit_quotations',
            'delete_quotations',
            'evaluate_quotations',

            // Purchase Order Management
            'view_purchase_orders',
            'create_purchase_orders',
            'edit_purchase_orders',
            'delete_purchase_orders',
            'approve_purchase_orders',

            // Delivery Management
            'view_deliveries',
            'create_deliveries',
            'edit_deliveries',
            'delete_deliveries',
            'inspect_deliveries',
            'accept_deliveries',
            'tag_delivery_assets',

            // Document Management
            'view_documents',
            'upload_documents',
            'delete_documents',

            // Audit Trail
            'view_audit_logs',
            'export_audit_logs',

            // Training Management
            'view_trainings',
            'create_trainings',
            'edit_trainings',
            'delete_trainings',

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
            'view_attendance',
            'approve_attendance',
            'export_attendance',
            'view_service_credits',
            'approve_service_credits',
            'view_inventory',
            'issue_inventory',
            'approve_inventory_adjustments',
            'view_budget',
            'view_expenses',
            'approve_expense',
            'view_suppliers',
            'view_purchase_requests',
            'approve_purchase_request',
            'view_quotations',
            'evaluate_quotations',
            'view_purchase_orders',
            'approve_purchase_orders',
            'view_deliveries',
            'accept_deliveries',
            'view_documents',
            'view_audit_logs',
            'view_trainings',
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
            'manage_user_permissions',
            'view_employees',
            'create_employees',
            'edit_employees',
            'view_201_file',
            'edit_201_file',
            'view_leave_requests',
            'create_leave_request',
            'recommend_leave',
            'manage_leave_credits',
            'view_service_records',
            'create_service_records',
            'edit_service_records',
            'view_attendance',
            'create_attendance',
            'edit_attendance',
            'delete_attendance',
            'approve_attendance',
            'manage_attendance_settings',
            'export_attendance',
            'upload_attendance_csv',
            'view_service_credits',
            'create_service_credits',
            'edit_service_credits',
            'delete_service_credits',
            'approve_service_credits',
            'apply_service_credit_offset',
            'manage_service_credit_rules',
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'delete_inventory',
            'issue_inventory',
            'approve_inventory_adjustments',
            'view_budget',
            'create_budget',
            'edit_budget',
            'delete_budget',
            'view_expenses',
            'create_expense',
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',
            'view_purchase_requests',
            'create_purchase_requests',
            'edit_purchase_requests',
            'delete_purchase_requests',
            'submit_purchase_request',
            'recommend_purchase_request',
            'view_quotations',
            'create_quotations',
            'edit_quotations',
            'delete_quotations',
            'evaluate_quotations',
            'view_purchase_orders',
            'create_purchase_orders',
            'edit_purchase_orders',
            'delete_purchase_orders',
            'view_deliveries',
            'create_deliveries',
            'edit_deliveries',
            'delete_deliveries',
            'inspect_deliveries',
            'accept_deliveries',
            'tag_delivery_assets',
            'view_documents',
            'upload_documents',
            'delete_documents',
            'view_audit_logs',
            'export_audit_logs',
            'view_trainings',
            'create_trainings',
            'edit_trainings',
            'delete_trainings',
            'view_reports',
            'export_reports',
        ]);

        // 4. Teacher/Staff - Limited access
        $teacherStaff = Role::firstOrCreate(['name' => 'Teacher/Staff']);
        $teacherStaff->syncPermissions([
            'view_employees', // Can view colleagues
            'create_leave_request',
            'view_leave_requests', // Own only
            'view_attendance', // Own records only
            'view_trainings', // Can view own training records
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
