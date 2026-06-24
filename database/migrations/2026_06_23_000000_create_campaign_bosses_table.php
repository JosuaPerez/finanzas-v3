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
        Schema::create('campaign_bosses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('name');

            // Health pool
            $table->unsignedInteger('max_health');
            $table->unsignedInteger('current_health');

            // Reward granted on defeat
            $table->unsignedInteger('experience_reward')->default(0);

            // Campaign ordering & state
            $table->unsignedSmallInteger('order')->default(0)->index();
            $table->boolean('is_defeated')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_bosses');
    }
};
