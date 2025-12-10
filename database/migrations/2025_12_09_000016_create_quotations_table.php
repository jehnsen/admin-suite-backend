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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');

            $table->string('quotation_number')->unique();
            $table->date('quotation_date');
            $table->date('validity_date')->nullable(); // Quote valid until

            // Pricing Summary
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);

            // Terms
            $table->string('payment_terms')->nullable(); // e.g., "Net 30", "COD"
            $table->string('delivery_terms')->nullable(); // e.g., "7-10 days", "FOB"
            $table->text('terms_and_conditions')->nullable();

            // Evaluation
            $table->boolean('is_selected')->default(false);
            $table->integer('ranking')->nullable(); // 1st, 2nd, 3rd choice
            $table->decimal('evaluation_score', 5, 2)->nullable(); // Scoring for bid evaluation
            $table->text('evaluation_remarks')->nullable();

            $table->enum('status', ['Pending', 'Evaluated', 'Selected', 'Rejected'])->default('Pending');
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('quotation_number');
            $table->index('purchase_request_id');
            $table->index('supplier_id');
            $table->index('is_selected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
