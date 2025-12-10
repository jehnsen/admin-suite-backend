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
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->string('ca_number')->unique(); // e.g., CA-2025-0001
            $table->date('ca_date');

            // Employee/Payee
            $table->foreignId('employee_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');

            // Purpose
            $table->text('purpose');
            $table->string('project_activity')->nullable();

            // Amount
            $table->decimal('amount', 15, 2);

            // Fund Source
            $table->enum('fund_source', ['MOOE', 'SEF', 'Special Education Fund', 'Maintenance Fund', 'Other'])->default('MOOE');
            $table->foreignId('budget_id')->nullable()->constrained()->onDelete('set null');

            // Dates
            $table->date('date_needed');
            $table->date('due_date_liquidation'); // Deadline for liquidation

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Release
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('released_at')->nullable();

            // Liquidation
            $table->decimal('liquidated_amount', 15, 2)->default(0.00);
            $table->decimal('unliquidated_balance', 15, 2)->default(0.00);
            $table->date('liquidation_date')->nullable();

            // Status
            $table->enum('status', [
                'Pending',
                'Approved',
                'Released',
                'Partially Liquidated',
                'Fully Liquidated',
                'Overdue',
                'Cancelled'
            ])->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ca_number');
            $table->index('employee_id');
            $table->index('status');
            $table->index('due_date_liquidation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
