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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Ej: Préstamo Vehículo
            $table->decimal('balance', 10, 2); // Cuánto debes en total
            $table->decimal('interest_rate', 5, 2)->default(0); // Ej: 18.5%
            $table->decimal('minimum_payment', 10, 2)->default(0); // Pago mínimo requerido
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
