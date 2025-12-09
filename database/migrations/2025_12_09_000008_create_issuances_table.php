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
        Schema::create('issuances', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_to_employee_id')->constrained('employees')->cascadeOnDelete();

            // Issuance Details
            $table->string('issuance_number')->unique(); // e.g., "IS-2024-0001"
            $table->date('issued_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();

            // Purpose & Accountability
            $table->enum('purpose', [
                'Official Use',
                'Personal Accountability',
                'Project Use',
                'Temporary Assignment',
                'Other'
            ]);
            $table->text('purpose_details')->nullable();

            // Custodianship
            $table->enum('custodianship_type', [
                'Permanent',
                'Temporary',
                'Shared'
            ])->default('Permanent');

            // Status
            $table->enum('status', [
                'Active',      // Currently issued and in use
                'Returned',    // Returned to inventory
                'Transferred', // Transferred to another employee
                'Lost',        // Reported as lost
                'Damaged'      // Returned but damaged
            ])->default('Active');

            // Condition on Return
            $table->enum('condition_on_return', [
                'Good',
                'Fair',
                'Poor',
                'Damaged',
                'Not Applicable'
            ])->nullable();

            $table->text('return_remarks')->nullable();

            // Approval
            $table->foreignId('issued_by')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();

            // Acknowledgement
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgement_signature_path')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('issuance_number');
            $table->index('inventory_item_id');
            $table->index('issued_to_employee_id');
            $table->index('status');
            $table->index(['issued_date', 'actual_return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuances');
    }
};
