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
        Schema::create('physical_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_number')->unique();
            $table->date('count_date');

            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');

            // Count Details
            $table->integer('system_quantity'); // From stock card
            $table->integer('actual_quantity'); // Physical count
            $table->integer('variance'); // Difference (actual - system)

            // Variance Classification
            $table->enum('variance_type', ['Shortage', 'Overage', 'Match'])->nullable();

            // Count Team
            $table->foreignId('counted_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            // Status
            $table->enum('status', [
                'Counted',
                'Verified',
                'Adjustment Created',
                'Completed'
            ])->default('Counted');

            // Investigation (for variances)
            $table->text('variance_explanation')->nullable();
            $table->text('corrective_action')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('count_number');
            $table->index('count_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_counts');
    }
};
