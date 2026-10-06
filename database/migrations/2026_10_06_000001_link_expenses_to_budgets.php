<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('budget_deducted')->default(false);
            $table->uuid('request_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropUnique(['request_id']);
            $table->dropColumn(['budget_id', 'budget_deducted', 'request_id']);
        });
    }
};
