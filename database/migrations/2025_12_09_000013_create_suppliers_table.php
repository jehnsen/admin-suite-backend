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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->unique();
            $table->string('business_name');
            $table->string('trade_name')->nullable();
            $table->string('owner_name');
            $table->enum('business_type', ['Sole Proprietorship', 'Partnership', 'Corporation', 'Cooperative']);

            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone_number', 20);
            $table->string('mobile_number', 20)->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('zip_code', 10);

            // Government Registrations
            $table->string('tin', 20);
            $table->string('bir_certificate_number')->nullable();
            $table->string('dti_registration')->nullable(); // For sole proprietorship
            $table->string('sec_registration')->nullable(); // For corporations
            $table->string('mayors_permit')->nullable();
            $table->string('philgeps_registration')->nullable();

            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Categories and Classification
            $table->json('product_categories')->nullable(); // ['Office Supplies', 'IT Equipment', etc.]
            $table->enum('supplier_classification', ['Small', 'Medium', 'Large'])->default('Small');

            // Performance Tracking
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 to 5.00
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_amount_transacted', 15, 2)->default(0.00);

            // Status
            $table->enum('status', ['Active', 'Inactive', 'Blacklisted'])->default('Active');
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('supplier_code');
            $table->index('business_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
