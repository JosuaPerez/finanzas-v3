<?php

namespace App\Services;

use App\Models\CampaignBoss;
use App\Models\User;

class CombatService
{
    /**
     * Apply damage dealt by the user to their current active campaign boss.
     *
     * Workflow:
     *  1. Find the earliest non-defeated boss for the user (ordered by `order`).
     *  2. Subtract $damageAmount from the boss's current_health.
     *  3. If the boss is brought to 0 HP, mark it as defeated and award its
     *     experience_reward to the user via the existing RPG engine (addXp).
     *
     * @param  User   $user         The attacking user.
     * @param  float  $damageAmount Damage to inflict (must be > 0).
     * @return bool   True if the boss was defeated by this attack, false otherwise.
     */
    public function processAttack(User $user, float $damageAmount): bool
    {
        // 1. Locate the active boss
        $boss = CampaignBoss::where('user_id', $user->id)
            ->where('is_defeated', false)
            ->orderBy('order')
            ->first();

        if (! $boss) {
            return false; // No active boss — campaign complete or not yet seeded.
        }

        // 2. Apply damage, floor health at 0
        $boss->current_health = max(0, $boss->current_health - (int) ceil($damageAmount));

        // 3. Check for defeat
        $bossDefeated = $boss->current_health <= 0;

        if ($bossDefeated) {
            $boss->current_health = 0;
            $boss->is_defeated    = true;
        }

        $boss->save();

        // 4. Award XP and trigger level-up check via the existing RPG engine
        if ($bossDefeated) {
            $user->addXp($boss->experience_reward);
        }

        return $bossDefeated;
    }
}
