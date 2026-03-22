<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('currency', 3)->default('DOP'); // 'DOP' o 'USD'
            $table->decimal('overdraft_percentage', 5, 2)->default(0)->nullable(); // % de sobregiro
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['currency', 'overdraft_percentage']);
        });
    }
};