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
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained()->onDelete('restrict');

            // Item Details (snapshot)
            $table->integer('item_number')->default(1);
            $table->string('item_description');
            $table->string('unit_of_measure');

            // Quantities
            $table->integer('quantity_ordered'); // From PO
            $table->integer('quantity_delivered');
            $table->integer('quantity_accepted')->default(0);
            $table->integer('quantity_rejected')->default(0);

            // Inspection Results
            $table->enum('item_condition', ['Good', 'Damaged', 'Defective', 'Incomplete'])->default('Good');
            $table->text('inspection_notes')->nullable();

            // Serial/Batch Tracking (for equipment)
            $table->json('serial_numbers')->nullable(); // For items with serial numbers
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable(); // For consumables

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('delivery_id');
            $table->index('purchase_order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
