<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('request_id')->unique();
            $table->string('operation');
            $table->string('payload_hash', 64);
            $table->json('response');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_submissions');
    }
};
