<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->enum('document_type', ['PAR', 'ICS', 'General'])->default('General')->after('uuid');
        });

        // Backfill UUIDs for existing rows
        DB::table('issuances')->whereNull('uuid')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('issuances')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });

        Schema::table('issuances', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'document_type']);
        });
    }
};
