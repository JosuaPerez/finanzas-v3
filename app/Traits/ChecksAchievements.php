<?php

namespace App\Traits;

use App\Models\Achievement;
use App\Models\User;

/**
 * Lightweight achievement checker — same pattern as SurvivalMechanics.
 *
 * Drop `use ChecksAchievements;` into any controller, then call:
 *   $this->checkAchievements($user, 'event_name');
 *
 * Checks are synchronous and cheap (single count queries).
 * Silent on failure — never interrupts the main flow.
 */
trait ChecksAchievements
{
    public function checkAchievements(User $user, string $event): void
    {
        $achievement = Achievement::where('condition_type', $event)->first();

        if (! $achievement) {
            return;
        }

        // Already unlocked — nothing to do.
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return;
        }

        $met = match ($event) {
            'debt_payment_made'  => true,   // any payment triggers this
            'debt_eliminated'    => true,   // caller already verified balance = 0
            'expenses_registered'=> \App\Models\Expense::where('user_id', $user->id)->count()
                                        >= $achievement->target_value,
            default              => false,
        };

        if ($met) {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);
        }
    }
}
