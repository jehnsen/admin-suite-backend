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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->date('pr_date');

            // Requestor Information
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->string('department');
            $table->string('section')->nullable();

            // Purpose and Details
            $table->text('purpose');
            $table->enum('fund_source', ['MOOE', 'SEF', 'Special Education Fund', 'Maintenance Fund', 'Other'])->default('MOOE');
            $table->string('fund_cluster')->nullable();
            $table->string('ppmp_reference')->nullable(); // Project Procurement Management Plan reference

            // Procurement Details
            $table->enum('procurement_mode', [
                'Small Value Procurement',
                'Shopping',
                'Public Bidding',
                'Limited Source Bidding',
                'Direct Contracting',
                'Repeat Order',
                'Negotiated Procurement'
            ])->default('Small Value Procurement');

            $table->decimal('estimated_budget', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);

            // Dates
            $table->date('date_needed')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_location')->nullable();

            // Approval Workflow
            $table->enum('status', [
                'Draft',
                'Pending',
                'Recommended', // By immediate supervisor
                'Approved', // By School Head/Principal
                'For Quotation', // Ready for canvassing
                'For PO Creation',
                'Completed',
                'Disapproved',
                'Cancelled'
            ])->default('Draft');

            $table->foreignId('recommended_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('recommended_at')->nullable();
            $table->text('recommendation_remarks')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();

            $table->foreignId('disapproved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('disapproved_at')->nullable();
            $table->text('disapproval_reason')->nullable();

            // Notes
            $table->text('remarks')->nullable();
            $table->text('terms_and_conditions')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pr_number');
            $table->index('status');
            $table->index('pr_date');
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
