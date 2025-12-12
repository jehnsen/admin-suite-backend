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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Training Information
            $table->string('training_title');
            $table->text('description')->nullable();
            $table->enum('training_type', [
                'Seminar',
                'Workshop',
                'Conference',
                'Training Course',
                'Webinar',
                'Orientation',
                'Professional Development',
                'Certification Program',
                'Other'
            ]);

            // Provider/Organizer Information
            $table->string('conducted_by')->nullable(); // Training provider/organizer
            $table->string('venue')->nullable();
            $table->enum('venue_type', ['In-house', 'External', 'Online'])->default('External');

            // Schedule
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('number_of_hours', 6, 2)->nullable(); // Total training hours
            $table->decimal('ld_units', 6, 2)->nullable(); // Learning and Development Units

            // Certificate Details
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->string('certificate_file_path')->nullable(); // Path to uploaded certificate

            // Sponsorship/Funding
            $table->enum('sponsorship', ['Government', 'Private', 'Self-funded', 'Scholarship'])->default('Government');
            $table->decimal('cost', 10, 2)->nullable();

            // Status and Remarks
            $table->enum('status', ['Completed', 'Ongoing', 'Cancelled', 'Pending'])->default('Completed');
            $table->text('remarks')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better query performance
            $table->index('employee_id');
            $table->index('training_type');
            $table->index('status');
            $table->index(['date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
