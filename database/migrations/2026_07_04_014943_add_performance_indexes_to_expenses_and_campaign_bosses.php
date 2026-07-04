<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance migration: composite indexes for the two unindexed tables.
 *
 * expenses:
 *   - Dashboard fetches `where('user_id')->latest()->take(5)` on every cache miss.
 *   - A composite (user_id, created_at) index satisfies both the WHERE clause
 *     and the ORDER BY in a single index scan — no filesort.
 *
 * campaign_bosses:
 *   - CombatService does `where('user_id')->where('is_defeated', false)->orderBy('order')`
 *     on every attack. The existing `order` single-column index is useless here;
 *     the optimizer needs user_id first, then can use is_defeated + order.
 */
return new class extends Migration
{
    public function up(): void
    {
        // expenses: covers WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'expenses_user_id_created_at_idx');
        });

        // campaign_bosses: covers WHERE user_id = ? AND is_defeated = 0 ORDER BY order ASC
        Schema::table('campaign_bosses', function (Blueprint $table) {
            $table->index(['user_id', 'is_defeated', 'order'], 'campaign_bosses_user_defeated_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_user_id_created_at_idx');
        });

        Schema::table('campaign_bosses', function (Blueprint $table) {
            $table->dropIndex('campaign_bosses_user_defeated_order_idx');
        });
    }
};
