<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('holiday_date');
            $table->string('holiday_name');
            $table->enum('type', ['regular', 'special_non_working', 'special_working'])->default('regular');
            $table->timestamps();

            $table->unique('holiday_date');
            $table->index('holiday_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
