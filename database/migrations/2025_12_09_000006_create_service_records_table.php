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
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Service Record Details
            $table->date('date_from');
            $table->date('date_to')->nullable(); // Null means current position

            $table->string('designation');
            $table->enum('status_of_appointment', [
                'Permanent',
                'Temporary',
                'Casual',
                'Contractual',
                'Substitute'
            ]);

            $table->integer('salary_grade');
            $table->integer('step_increment')->default(1);
            $table->decimal('monthly_salary', 10, 2);

            $table->string('station_place_of_assignment');
            $table->string('office_entity');

            // Government Service
            $table->enum('government_service', ['Yes', 'No'])->default('Yes');

            // Action Taken
            $table->enum('action_type', [
                'New Appointment',
                'Promotion',
                'Transfer',
                'Reclassification',
                'Demotion',
                'Detail',
                'Secondment',
                'Reassignment'
            ]);

            // Supporting Documents
            $table->string('appointment_authority')->nullable(); // e.g., "CSC Resolution No."
            $table->date('appointment_date')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index(['date_from', 'date_to']);
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};
