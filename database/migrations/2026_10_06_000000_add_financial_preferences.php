<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_currency', 3)->default('DOP');
            $table->string('number_locale', 10)->default('es-DO');
            $table->timestamp('financial_preferences_set_at')->nullable();
        });
        // Existing budgets and quick expenses were always denominated in DOP.
        foreach (['budgets', 'expenses'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('currency', 3)->default('DOP'));
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'preferred_currency', 'number_locale', 'financial_preferences_set_at',
        ]));
        foreach (['budgets', 'expenses'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('currency'));
        }
    }
};
