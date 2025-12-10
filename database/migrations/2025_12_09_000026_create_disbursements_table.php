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
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->string('dv_number')->unique(); // Disbursement Voucher Number
            $table->date('dv_date');

            // Payee Information
            $table->string('payee_name');
            $table->text('payee_address')->nullable();
            $table->string('payee_tin')->nullable();

            // References
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cash_advance_id')->nullable()->constrained()->onDelete('set null');

            // Purpose and Amount
            $table->text('purpose');
            $table->decimal('amount', 15, 2);

            // Fund Source
            $table->enum('fund_source', ['MOOE', 'SEF', 'Special Education Fund', 'Maintenance Fund', 'Other'])->default('MOOE');
            $table->foreignId('budget_id')->nullable()->constrained()->onDelete('set null');

            // Payment Details
            $table->enum('payment_mode', ['Check', 'Cash', 'Bank Transfer', 'Other'])->default('Check');
            $table->string('check_number')->nullable();
            $table->date('check_date')->nullable();
            $table->string('bank_name')->nullable();

            // Tax Withholding
            $table->decimal('gross_amount', 15, 2)->default(0.00);
            $table->decimal('tax_withheld', 12, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2)->default(0.00);

            // Certification
            $table->foreignId('certified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('certified_at')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Payment
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();

            // Status
            $table->enum('status', [
                'Pending',
                'Certified',
                'Approved',
                'Paid',
                'Cancelled'
            ])->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('dv_number');
            $table->index('dv_date');
            $table->index('status');
            $table->index('fund_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
