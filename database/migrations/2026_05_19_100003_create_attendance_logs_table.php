<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->constrained('attendance_import_batches')->cascadeOnDelete();
            $table->date('log_date');
            $table->dateTime('punched_at');
            $table->enum('source', ['biometric_upload', 'manual_entry'])->default('biometric_upload');
            $table->timestamps();

            $table->index('employee_id');
            $table->index('log_date');
            $table->index(['employee_id', 'log_date']);
            $table->index('import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
