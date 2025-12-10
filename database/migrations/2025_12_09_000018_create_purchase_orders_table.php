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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('po_date');

            // References
            $table->foreignId('purchase_request_id')->constrained()->onDelete('restrict');
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');

            // Supplier Contact (snapshot at time of PO)
            $table->string('supplier_name');
            $table->text('supplier_address');
            $table->string('supplier_contact');
            $table->string('supplier_tin');

            // Financial Details
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);

            // Fund Source
            $table->enum('fund_source', ['MOOE', 'SEF', 'Special Education Fund', 'Maintenance Fund', 'Other'])->default('MOOE');
            $table->string('fund_cluster')->nullable();
            $table->foreignId('budget_id')->nullable()->constrained()->onDelete('set null');

            // Delivery Details
            $table->string('delivery_location');
            $table->date('delivery_date');
            $table->string('delivery_terms')->nullable();

            // Payment Terms
            $table->string('payment_terms')->nullable();
            $table->enum('payment_method', ['Check', 'Cash', 'Bank Transfer', 'Other'])->nullable();

            // Terms and Conditions
            $table->text('terms_and_conditions')->nullable();
            $table->text('special_instructions')->nullable();

            // Status Tracking
            $table->enum('status', [
                'Pending',
                'Approved',
                'Sent to Supplier',
                'Partially Delivered',
                'Fully Delivered',
                'Cancelled',
                'Completed'
            ])->default('Pending');

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Prepared By
            $table->foreignId('prepared_by')->constrained('users')->onDelete('restrict');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('po_number');
            $table->index('status');
            $table->index('supplier_id');
            $table->index('purchase_request_id');
            $table->index('po_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
