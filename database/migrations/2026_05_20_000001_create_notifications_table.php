<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Who receives this notification (null = broadcast to all authenticated users)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Notification classification
            $table->string('type');         // leave_pending | low_stock | budget_alert | system | etc.
            $table->string('badge');        // Pending | Alert | Info | Success | Warning
            $table->string('title');
            $table->text('message');

            // Link back to the source record (polymorphic-style, without enforcing FK)
            $table->string('subject_type')->nullable();  // App\Models\LeaveRequest
            $table->string('subject_uuid')->nullable();  // the uuid of the record

            // Arbitrary extra data (amounts, percentages, names, etc.)
            $table->json('meta')->nullable();

            // Per-user interaction state
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'dismissed_at']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
