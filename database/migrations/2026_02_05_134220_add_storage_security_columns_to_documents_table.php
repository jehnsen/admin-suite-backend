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
        Schema::table('documents', function (Blueprint $table) {
            // Add security and storage classification columns
            $table->boolean('is_sensitive')->default(false)->after('is_mandatory')
                ->comment('Whether document contains sensitive/confidential data');

            $table->enum('storage_disk', ['public', 'private'])->default('public')->after('is_sensitive')
                ->comment('Storage disk: public for non-sensitive, private for sensitive docs');

            // Add index for querying sensitive documents
            $table->index('is_sensitive');
            $table->index('storage_disk');
        });

        // Update existing documents to mark financial docs as sensitive
        DB::table('documents')->whereIn('document_type', [
            'official_receipt',
            'purchase_order',
            'delivery_receipt',
            'iar'
        ])->update([
            'is_sensitive' => true,
            'storage_disk' => 'public' // Keep existing files in public for backward compatibility
        ]);

        // Mark all liquidation-related documents as sensitive
        DB::table('documents')->where('documentable_type', 'LIKE', '%Liquidation%')
            ->orWhere('documentable_type', 'LIKE', '%CashAdvance%')
            ->orWhere('documentable_type', 'LIKE', '%Disbursement%')
            ->update([
                'is_sensitive' => true,
                'storage_disk' => 'public' // Keep existing files in public for backward compatibility
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['is_sensitive']);
            $table->dropIndex(['storage_disk']);
            $table->dropColumn(['is_sensitive', 'storage_disk']);
        });
    }
};
