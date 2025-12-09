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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Leave Details
            $table->enum('leave_type', [
                'Vacation Leave',
                'Sick Leave',
                'Maternity Leave',
                'Paternity Leave',
                'Special Privilege Leave',
                'Solo Parent Leave',
                'Study Leave',
                'VAWC Leave',
                'Rehabilitation Leave',
                'Special Leave Benefits for Women',
                'Special Emergency Leave',
                'Adoption Leave'
            ]);

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 5, 2);

            // For Sick Leave
            $table->enum('sick_leave_type', ['In Hospital', 'Out Patient'])->nullable();
            $table->string('illness')->nullable();

            // For Special Leaves
            $table->text('reason')->nullable();

            // Workflow Status
            $table->enum('status', [
                'Pending',
                'Recommended',
                'Approved',
                'Disapproved',
                'Cancelled'
            ])->default('Pending');

            // Approvers
            $table->foreignId('recommended_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable();
            $table->text('recommendation_remarks')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();

            // Disapproval
            $table->foreignId('disapproved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('disapproved_at')->nullable();
            $table->text('disapproval_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index('leave_type');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
