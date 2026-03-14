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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Ej: Fondo de Emergencia
            $table->decimal('target_amount', 10, 2); // La meta a alcanzar
            $table->decimal('current_amount', 10, 2)->default(0); // Cuánto llevas ahorrado
            $table->date('deadline')->nullable(); // Fecha límite opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
