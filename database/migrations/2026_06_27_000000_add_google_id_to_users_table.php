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
        Schema::table('users', function (Blueprint $table) {
            // Unique Google account identifier; null for traditional users.
            $table->string('google_id')->nullable()->unique()->after('email');

            // Allow null password so Google SSO users can register without one.
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');

            // Restore password to non-nullable.
            $table->string('password')->nullable(false)->change();
        });
    }
};
