<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_slip_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisition_slip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();

            $table->string('stock_number')->nullable();
            $table->string('description')->nullable();
            $table->string('unit_of_measure', 50);

            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->integer('quantity_requested')->unsigned();
            $table->integer('quantity_approved')->unsigned()->nullable();
            $table->integer('quantity_issued')->unsigned()->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('requisition_slip_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_slip_items');
    }
};
