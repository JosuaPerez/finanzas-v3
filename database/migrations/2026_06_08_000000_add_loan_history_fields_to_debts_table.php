<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add historical loan data columns:
     *  - fecha_inicio            → the date the loan was originally disbursed
     *  - plazo_original_meses    → the agreed repayment term in months
     *
     * Both are nullable so existing records and credit-card debts are unaffected.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('original_amount');
            $table->unsignedSmallInteger('plazo_original_meses')->nullable()->after('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'plazo_original_meses']);
        });
    }
};
