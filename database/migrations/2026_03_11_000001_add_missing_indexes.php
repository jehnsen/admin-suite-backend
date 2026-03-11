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
        // users.email — unique index (search/login lookups)
        if (!Schema::hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        // employees.email — unique index (search/login lookups)
        if (!Schema::hasIndex('employees', 'employees_email_unique')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        // employees.status — index (frequent filter in queries)
        if (!Schema::hasIndex('employees', 'employees_status_index')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_status_index');
            $table->dropUnique('employees_email_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });
    }
};
