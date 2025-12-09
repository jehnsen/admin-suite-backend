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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Relationship
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();

            // Expense Identification
            $table->string('expense_number')->unique(); // e.g., "EXP-2024-0001"
            $table->string('expense_name');
            $table->text('description')->nullable();

            // Expense Details
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);

            // Payment Information
            $table->enum('payment_method', [
                'Cash',
                'Check',
                'Bank Transfer',
                'Credit Card',
                'Petty Cash',
                'Other'
            ]);

            $table->string('payee'); // Who received the payment
            $table->string('reference_number')->nullable(); // Check number, transaction ID, etc.

            // Supporting Documents
            $table->string('invoice_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('po_number')->nullable(); // Purchase Order

            // Categorization
            $table->string('category'); // e.g., "Supplies", "Services", "Equipment"
            $table->string('sub_category')->nullable();

            // Purpose/Project
            $table->text('purpose');
            $table->string('project_name')->nullable();

            // Approval Workflow
            $table->foreignId('requested_by')->constrained('employees')->cascadeOnDelete();

            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('disbursed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();

            // Status
            $table->enum('status', [
                'Draft',
                'Pending Approval',
                'Approved',
                'Disbursed',
                'Cancelled',
                'Rejected'
            ])->default('Draft');

            // Liquidation (for cash advances)
            $table->boolean('requires_liquidation')->default(false);
            $table->date('liquidation_deadline')->nullable();
            $table->date('liquidated_at')->nullable();
            $table->enum('liquidation_status', [
                'Not Required',
                'Pending',
                'Completed',
                'Overdue'
            ])->default('Not Required');

            // Attachments
            $table->json('attachments')->nullable(); // Array of file paths

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('expense_number');
            $table->index('budget_id');
            $table->index('expense_date');
            $table->index('status');
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
