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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();

            // Item Identification
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('category'); // e.g., "Office Equipment", "ICT Equipment", "Furniture"
            $table->string('unit_of_measure'); // e.g., "piece", "unit", "set"

            // Property Details
            $table->string('serial_number')->nullable();
            $table->string('property_number')->nullable()->unique(); // DepEd Property Number
            $table->string('model')->nullable();
            $table->string('brand')->nullable();

            // Financial Information
            $table->decimal('unit_cost', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_cost', 12, 2);

            // Acquisition Details
            $table->enum('fund_source', [
                'MOOE',
                'SEF',
                'Donation',
                'DepEd Central',
                'LGU',
                'Other'
            ]);
            $table->string('supplier')->nullable();
            $table->date('date_acquired');
            $table->string('po_number')->nullable(); // Purchase Order Number
            $table->string('invoice_number')->nullable();

            // Condition & Status
            $table->enum('condition', [
                'Serviceable',
                'Unserviceable',
                'For Repair',
                'For Disposal',
                'Disposed'
            ])->default('Serviceable');

            $table->enum('status', [
                'In Stock',
                'Issued',
                'Under Maintenance',
                'Lost',
                'Stolen',
                'Disposed'
            ])->default('In Stock');

            // Location
            $table->string('location')->nullable(); // Storage location when not issued

            // Depreciation (if applicable)
            $table->integer('estimated_useful_life_years')->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->decimal('accumulated_depreciation', 10, 2)->default(0);
            $table->decimal('book_value', 10, 2)->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('item_code');
            $table->index('category');
            $table->index('condition');
            $table->index('status');
            $table->index('fund_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
