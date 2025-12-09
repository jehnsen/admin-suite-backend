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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            // Budget Identification
            $table->string('budget_code')->unique(); // e.g., "MOOE-2024-001"
            $table->string('budget_name'); // e.g., "Training and Development Fund"
            $table->text('description')->nullable();

            // Budget Source
            $table->enum('fund_source', [
                'MOOE',           // Maintenance and Other Operating Expenses
                'SEF',            // Special Education Fund
                'DepEd Central',
                'LGU',
                'Donation',
                'Other'
            ]);

            // Budget Classification (SIP/AIP)
            $table->enum('classification', [
                'SIP',  // School Improvement Plan
                'AIP',  // Annual Implementation Plan
                'GAA',  // General Appropriations Act
                'Other'
            ]);

            // Fiscal Year
            $table->year('fiscal_year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4'])->nullable();

            // Budget Amounts
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('utilized_amount', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2);

            // Budget Category/Line Item
            $table->string('category'); // e.g., "Personnel Services", "Capital Outlay", "Operating Expenses"
            $table->string('sub_category')->nullable(); // More specific categorization

            // Budget Period
            $table->date('start_date');
            $table->date('end_date');

            // Status
            $table->enum('status', [
                'Draft',
                'Approved',
                'Active',
                'Closed',
                'Cancelled'
            ])->default('Draft');

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Responsible Person
            $table->foreignId('managed_by')->nullable()->constrained('employees')->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('budget_code');
            $table->index('fund_source');
            $table->index('classification');
            $table->index('fiscal_year');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
