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
        Schema::dropIfExists('attendance_records');

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            // Employee relationship
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Date and time tracking
            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->time('lunch_out')->nullable();
            $table->time('lunch_in')->nullable();

            // Status and calculations
            $table->enum('status', [
                'Present',
                'Absent',
                'On Leave',
                'Half-Day',
                'Holiday',
                'Weekend',
                'Service Credit Used'
            ])->default('Present');

            $table->decimal('undertime_hours', 4, 2)->default(0.00)
                ->comment('Hours of undertime calculated automatically');
            $table->integer('late_minutes')->default(0)
                ->comment('Minutes late calculated automatically');
            $table->decimal('overtime_hours', 4, 2)->default(0.00)
                ->comment('Hours of overtime calculated automatically');

            // Metadata
            $table->text('remarks')->nullable();
            $table->enum('import_source', [
                'Manual Entry',
                'CSV Upload',
                'Biometric Sync',
                'Mobile App'
            ])->default('Manual Entry');

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one record per employee per date
            $table->unique(['employee_id', 'attendance_date'], 'unique_employee_attendance_date');

            // Indexes for performance
            $table->index('attendance_date');
            $table->index('status');
            $table->index(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
