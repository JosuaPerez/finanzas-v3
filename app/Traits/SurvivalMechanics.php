<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;

trait SurvivalMechanics
{
    /**
     * Register a daily action for the given user.
     *
     * Rules:
     *  - If last_action_date is today  → do nothing (already counted).
     *  - If last_action_date is yesterday → increment current_streak,
     *      update longest_streak if needed, set last_action_date to today.
     *  - If last_action_date is older than yesterday or null → reset
     *      current_streak to 1 and set last_action_date to today.
     *
     * Streak milestone XP rewards (awarded once per milestone hit):
     *  3 days  →  +50 XP
     *  7 days  → +100 XP
     * 14 days  → +250 XP
     * 30 days  → +500 XP
     */
    public function registerDailyAction(User $user): void
    {
        $today     = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $lastDate  = $user->last_action_date
            ? Carbon::parse($user->last_action_date)->toDateString()
            : null;

        // Already logged an action today — nothing to do.
        if ($lastDate === $today) {
            return;
        }

        if ($lastDate === $yesterday) {
            // Consecutive day — keep the streak alive.
            $user->current_streak += 1;
        } else {
            // Streak broken (or first action ever) — restart from 1.
            $user->current_streak = 1;
        }

        // Update the all-time record if we just beat it.
        if ($user->current_streak > $user->longest_streak) {
            $user->longest_streak = $user->current_streak;
        }

        $user->last_action_date = $today;
        $user->save();

        // ── Streak milestone XP rewards ───────────────────────────────────────
        // Only fires when the streak increments (not on reset to 1, handled by
        // the match default). addXp() handles level-up detection internally.
        $bonus = match ($user->current_streak) {
            3  =>  50,
            7  => 100,
            14 => 250,
            30 => 500,
            default => 0,
        };

        if ($bonus > 0) {
            $user->addXp($bonus);
            session()->flash('streak_bonus', [
                'days'  => $user->current_streak,
                'bonus' => $bonus,
            ]);
        }
    }
}
