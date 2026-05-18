<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'users',
        'employees',
        'leave_requests',
        'service_records',
        'trainings',
        'attendance_records',
        'service_credits',
        'budgets',
        'cash_advances',
        'disbursements',
        'liquidations',
        'purchase_requests',
        'purchase_orders',
        'quotations',
        'suppliers',
        'deliveries',
        'inventory_items',
        'inventory_adjustments',
        'physical_counts',
        'stock_cards',
        'documents',
        'transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            // Backfill UUIDs for existing rows
            DB::table($table)->orderBy('id')->each(function ($row) use ($table) {
                DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            });

            Schema::table($table, function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
