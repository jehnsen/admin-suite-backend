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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_receipt_number')->unique();
            $table->date('delivery_date');
            $table->time('delivery_time')->nullable();

            // References
            $table->foreignId('purchase_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');

            // Delivery Details
            $table->string('supplier_dr_number')->nullable(); // Supplier's own DR number
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();

            // Delivery Person
            $table->string('delivered_by_name');
            $table->string('delivered_by_contact')->nullable();
            $table->string('vehicle_plate_number')->nullable();

            // Reception
            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('received_at');
            $table->string('received_location');

            // Inspection
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('inspected_at')->nullable();
            $table->enum('inspection_result', ['Passed', 'Failed', 'Partially Passed', 'Pending'])->default('Pending');
            $table->text('inspection_remarks')->nullable();

            // Acceptance
            $table->foreignId('accepted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accepted_at')->nullable();
            $table->text('acceptance_remarks')->nullable();

            // Status
            $table->enum('status', [
                'Pending Inspection',
                'Under Inspection',
                'Accepted',
                'Rejected',
                'Partially Accepted'
            ])->default('Pending Inspection');

            // Physical Condition
            $table->enum('condition', ['Good', 'Damaged', 'Incomplete', 'Mixed'])->default('Good');
            $table->text('condition_notes')->nullable();

            // Attachments
            $table->json('attachments')->nullable(); // Photos, scanned documents

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('delivery_receipt_number');
            $table->index('purchase_order_id');
            $table->index('delivery_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
