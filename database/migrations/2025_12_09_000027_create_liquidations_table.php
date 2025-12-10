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
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->string('liquidation_number')->unique();
            $table->date('liquidation_date');

            $table->foreignId('cash_advance_id')->constrained()->onDelete('restrict');

            // Amounts
            $table->decimal('cash_advance_amount', 15, 2);
            $table->decimal('total_expenses', 15, 2)->default(0.00);
            $table->decimal('amount_to_refund', 15, 2)->default(0.00); // If excess
            $table->decimal('additional_cash_needed', 15, 2)->default(0.00); // If short

            // Attachments
            $table->json('supporting_documents')->nullable(); // Receipts, invoices
            $table->text('summary_of_expenses')->nullable();

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_remarks')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Refund/Payment
            $table->date('refund_date')->nullable();
            $table->string('refund_or_number')->nullable(); // Official Receipt number
            $table->date('additional_payment_date')->nullable();

            // Status
            $table->enum('status', [
                'Pending',
                'Under Review',
                'Verified',
                'Approved',
                'Refund Pending',
                'Payment Pending',
                'Completed',
                'Rejected'
            ])->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('liquidation_number');
            $table->index('cash_advance_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidations');
    }
};
