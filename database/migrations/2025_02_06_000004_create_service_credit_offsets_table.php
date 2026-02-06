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
        Schema::create('service_credit_offsets', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('service_credit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Offset details
            $table->decimal('credits_used', 5, 2)
                ->comment('Amount of credits used for this offset');
            $table->date('offset_date')
                ->comment('Date when the credit was applied');
            $table->text('reason')->nullable();

            // Status tracking
            $table->enum('status', ['Applied', 'Reverted'])->default('Applied');
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revert_reason')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['service_credit_id', 'status']);
            $table->index('offset_date');
            $table->index(['employee_id', 'offset_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_credit_offsets');
    }
};
