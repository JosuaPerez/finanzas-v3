<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add daily_reward_claimed_at to users.
     *
     * Stores the date the Commander last claimed their daily quest rewards.
     * Nullable so existing users are unaffected (treated as never claimed).
     * Using date (not timestamp) makes the "claimed today?" check trivial:
     *   $user->daily_reward_claimed_at?->isToday()
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('daily_reward_claimed_at')->nullable()->after('last_action_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('daily_reward_claimed_at');
        });
    }
};
