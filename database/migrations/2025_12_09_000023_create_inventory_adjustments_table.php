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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->date('adjustment_date');

            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');

            // Adjustment Type
            $table->enum('adjustment_type', [
                'Increase', // Found items, donations, corrections
                'Decrease', // Lost, damaged, expired, stolen
                'Correction', // Fixing errors
                'Donation Received', // Donated equipment
                'Disposal', // Condemned/obsolete items
                'Transfer' // Between locations
            ]);

            // Quantities
            $table->integer('quantity_before');
            $table->integer('quantity_adjusted'); // Positive or negative
            $table->integer('quantity_after');

            // Reason
            $table->text('reason');
            $table->text('supporting_document')->nullable(); // Reference to memo, IAR, etc.

            // Approval
            $table->foreignId('prepared_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('adjustment_number');
            $table->index('adjustment_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
