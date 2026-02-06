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
        Schema::create('service_credits', function (Blueprint $table) {
            $table->id();

            // Employee relationship
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Credit details
            $table->enum('credit_type', [
                'Summer Work',
                'Holiday Work',
                'Overtime',
                'Special Duty',
                'Weekend Work'
            ]);
            $table->date('work_date');
            $table->text('description')->nullable();

            // Credits tracking
            $table->decimal('hours_worked', 5, 2)->default(0.00)
                ->comment('Total hours worked (8 hours = 1.0 credit)');
            $table->decimal('credits_earned', 5, 2)->default(0.00)
                ->comment('Service credits earned from hours worked');
            $table->decimal('credits_used', 5, 2)->default(0.00)
                ->comment('Credits already used for offsets');
            $table->decimal('credits_balance', 5, 2)->default(0.00)
                ->comment('Remaining credits available');

            // Approval workflow
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Expired'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Metadata
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('expiry_date')->nullable()
                ->comment('Credits expire 1 year after work_date');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index('work_date');
            $table->index('expiry_date');
            $table->index('credits_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_credits');
    }
};
