<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance migration: adds database indexes to frequently queried columns.
 *
 * Every query in this app filters by user_id. Without indexes, each query
 * performs a full table scan — O(n) cost that grows with every new user.
 *
 * Results after indexing:
 *   - Debt::where('user_id', ...) → uses index (key lookup)
 *   - Budget::where('user_id', ...)->latest() → uses composite index (user_id + created_at)
 *   - Goal::where('user_id', ...) → uses index (key lookup)
 */
return new class extends Migration
{
    public function up(): void
    {
        // debts: all queries filter by user_id (DebtController, DebtService, web.php)
        Schema::table('debts', function (Blueprint $table) {
            $table->index('user_id', 'debts_user_id_idx');
        });

        // budgets: queries filter by user_id AND sort by created_at (latest())
        // A composite index covers both the WHERE and the ORDER BY in one scan.
        Schema::table('budgets', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'budgets_user_id_created_at_idx');
        });

        // goals: all queries filter by user_id (GoalController)
        Schema::table('goals', function (Blueprint $table) {
            $table->index('user_id', 'goals_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex('debts_user_id_idx');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex('budgets_user_id_created_at_idx');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex('goals_user_id_idx');
        });
    }
};
