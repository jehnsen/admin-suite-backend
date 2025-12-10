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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_request_item_id')->nullable()->constrained()->onDelete('set null');

            // Item Details
            $table->integer('item_number')->default(1);
            $table->string('item_code')->nullable();
            $table->string('item_description');
            $table->string('brand_model')->nullable();
            $table->text('specifications')->nullable();

            // Quantities
            $table->string('unit_of_measure');
            $table->integer('quantity_ordered');
            $table->integer('quantity_delivered')->default(0);
            $table->integer('quantity_remaining')->default(0); // Auto-calculated

            // Pricing
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 15, 2); // quantity * unit_price

            // Delivery Status
            $table->enum('delivery_status', ['Pending', 'Partially Delivered', 'Fully Delivered'])->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('purchase_order_id');
            $table->index('purchase_request_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
