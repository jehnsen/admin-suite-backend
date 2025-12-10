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
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');

            // Item Details
            $table->integer('item_number')->default(1); // Line item number
            $table->string('item_code')->nullable(); // Stock/catalog code
            $table->string('item_description');
            $table->string('unit_of_measure'); // pcs, reams, boxes, units, etc.
            $table->integer('quantity');

            // Pricing
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00); // quantity * unit_cost

            // Specifications
            $table->text('specifications')->nullable();
            $table->string('category')->nullable(); // Office Supplies, IT Equipment, etc.

            // Stock Management
            $table->integer('stock_on_hand')->default(0);
            $table->integer('monthly_consumption')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('purchase_request_id');
            $table->index('item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
