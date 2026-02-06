<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Service credit balance tracking
            $table->decimal('service_credit_balance', 5, 2)->default(0.00)
                ->after('sick_leave_credits')
                ->comment('Available service credit balance (summer/holiday work credits)');

            // Standard working hours for DTR calculations
            $table->time('standard_time_in')->default('07:30:00')
                ->after('service_credit_balance')
                ->comment('Standard time in for undertime calculation');

            $table->time('standard_time_out')->default('16:30:00')
                ->after('standard_time_in')
                ->comment('Standard time out for undertime calculation');

            // Daily rate for deduction calculations
            $table->decimal('daily_rate', 10, 2)->nullable()
                ->after('monthly_salary')
                ->comment('Daily rate calculated from monthly salary (monthly_salary / 22)');

            // Index for service credit balance queries
            $table->index('service_credit_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['service_credit_balance']);
            $table->dropColumn([
                'service_credit_balance',
                'standard_time_in',
                'standard_time_out',
                'daily_rate',
            ]);
        });
    }
};
