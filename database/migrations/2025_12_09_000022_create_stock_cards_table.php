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
        Schema::create('stock_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');

            // Transaction Details
            $table->date('transaction_date');
            $table->string('reference_number'); // DR, IAR, RIS, etc.
            $table->enum('transaction_type', [
                'Stock In',
                'Stock Out',
                'Adjustment',
                'Transfer In',
                'Transfer Out',
                'Donation',
                'Return',
                'Disposal'
            ]);

            // Source/Destination
            $table->string('source_destination')->nullable(); // From/To location or person

            // Quantities
            $table->integer('quantity_in')->default(0);
            $table->integer('quantity_out')->default(0);
            $table->integer('balance')->default(0);

            // Unit Cost
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00);

            // References
            $table->foreignId('delivery_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('issuance_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');

            // Processed By
            $table->foreignId('processed_by')->constrained('users')->onDelete('restrict');

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('inventory_item_id');
            $table->index('transaction_date');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_cards');
    }
};
