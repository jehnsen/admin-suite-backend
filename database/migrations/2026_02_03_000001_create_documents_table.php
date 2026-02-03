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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->unsignedBigInteger('documentable_id');
            $table->string('documentable_type');

            // Document classification
            $table->enum('document_type', [
                'official_receipt',
                'purchase_order',
                'delivery_receipt',
                'property_card_photo',
                'iar',
                'other'
            ]);

            // File metadata
            $table->string('file_name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size'); // Size in bytes
            $table->string('mime_type', 100);
            $table->text('description')->nullable();

            // Audit and tracking
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->boolean('is_mandatory')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['documentable_id', 'documentable_type'], 'documentable_index');
            $table->index('document_type');
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
