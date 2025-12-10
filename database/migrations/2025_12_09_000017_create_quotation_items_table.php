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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_request_item_id')->nullable()->constrained()->onDelete('set null');

            // Item Details
            $table->integer('item_number')->default(1);
            $table->string('item_description');
            $table->string('brand_model')->nullable();
            $table->string('unit_of_measure');
            $table->integer('quantity');

            // Pricing
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 15, 2); // quantity * unit_price

            // Additional Details
            $table->text('specifications')->nullable();
            $table->string('delivery_period')->nullable(); // e.g., "5 days"
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('quotation_id');
            $table->index('purchase_request_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
