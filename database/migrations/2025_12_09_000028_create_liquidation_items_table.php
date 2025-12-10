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
        Schema::create('liquidation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->constrained()->onDelete('cascade');

            $table->integer('item_number')->default(1);
            $table->date('expense_date');
            $table->string('particulars'); // Description of expense
            $table->string('or_invoice_number')->nullable(); // Receipt/Invoice number
            $table->decimal('amount', 12, 2);

            $table->string('category')->nullable(); // Transportation, Food, Materials, etc.
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('liquidation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidation_items');
    }
};
