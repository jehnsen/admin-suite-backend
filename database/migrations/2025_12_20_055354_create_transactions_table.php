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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Transaction Identification
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');

            // Transaction Type and Category
            $table->enum('type', [
                'Income',           // Money received
                'Expense',          // Money spent
                'Transfer',         // Money moved between accounts
                'Adjustment'        // Corrections
            ]);

            $table->enum('category', [
                'Cash Advance',
                'Disbursement',
                'Liquidation',
                'Purchase Order Payment',
                'Salary',
                'Reimbursement',
                'Donation',
                'Other'
            ]);

            // Amount
            $table->decimal('amount', 15, 2);

            // References to other modules
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('disbursement_id')->nullable();
            $table->unsignedBigInteger('cash_advance_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            // Parties involved
            $table->string('payer')->nullable();           // Who paid
            $table->string('payee')->nullable();           // Who received
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();

            // Fund Source
            $table->enum('fund_source', [
                'MOOE',
                'SEF',
                'DepEd Central',
                'LGU',
                'Donation',
                'Other'
            ])->nullable();

            // Transaction Details
            $table->text('description');
            $table->string('reference_number')->nullable(); // OR, Check number, etc.
            $table->enum('payment_method', [
                'Cash',
                'Check',
                'Bank Transfer',
                'Online Payment',
                'Other'
            ])->default('Cash');

            // Status
            $table->enum('status', [
                'Pending',
                'Completed',
                'Cancelled',
                'Failed'
            ])->default('Completed');

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->text('remarks')->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('transaction_number');
            $table->index('transaction_date');
            $table->index('type');
            $table->index('category');
            $table->index('fund_source');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
