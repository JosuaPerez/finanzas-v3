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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Relación con el usuario
            $table->string('title'); // Ej: "1ra Quincena Marzo 2026"
            $table->decimal('income', 10, 2); // Cuánto cobró
            $table->decimal('fixed_expenses_total', 10, 2)->default(0); 
            $table->json('details')->nullable(); // Aquí guardaremos el JSON con las filas exactas de Vue
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
