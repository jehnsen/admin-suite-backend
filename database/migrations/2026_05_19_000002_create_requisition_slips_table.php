<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_slips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('ris_number')->unique(); // e.g. RIS-2026-0001

            $table->foreignId('requested_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('released_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('division_office')->nullable();
            $table->string('purpose');

            $table->enum('status', [
                'Draft',
                'Pending',
                'Approved',
                'Released',
                'Cancelled',
            ])->default('Draft');

            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->date('released_date')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('ris_number');
            $table->index('requested_by_employee_id');
            $table->index('status');
            $table->index('requested_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_slips');
    }
};
