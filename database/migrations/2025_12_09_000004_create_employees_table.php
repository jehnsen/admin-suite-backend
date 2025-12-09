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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Personal Information
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female']);
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Divorced']);

            // Contact Information
            $table->string('email')->unique();
            $table->string('mobile_number', 20);
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('zip_code', 10);

            // Employment Information
            $table->string('plantilla_item_no')->unique();
            $table->string('position');
            $table->string('position_title')->nullable();
            $table->integer('salary_grade')->nullable();
            $table->integer('step_increment')->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->enum('employment_status', ['Permanent', 'Temporary', 'Casual', 'Contractual', 'Substitute']);
            $table->date('date_hired');
            $table->date('date_separated')->nullable();

            // Government IDs
            $table->string('tin', 20)->nullable();
            $table->string('gsis_number', 20)->nullable();
            $table->string('philhealth_number', 20)->nullable();
            $table->string('pagibig_number', 20)->nullable();
            $table->string('sss_number', 20)->nullable();

            // Leave Credits (calculated values)
            $table->decimal('vacation_leave_credits', 5, 2)->default(0);
            $table->decimal('sick_leave_credits', 5, 2)->default(0);

            // Status
            $table->enum('status', ['Active', 'Inactive', 'On Leave', 'Retired', 'Resigned'])->default('Active');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_number');
            $table->index('position');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
