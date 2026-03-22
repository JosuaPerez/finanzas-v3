<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('type')->default('loan'); // 'loan' (Préstamo) o 'credit_card' (Tarjeta)
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('cutoff_date')->nullable(); // Día de corte (1-31)
            $table->integer('payment_date')->nullable(); // Día límite de pago (1-31)
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['type', 'credit_limit', 'cutoff_date', 'payment_date']);
        });
    }
};