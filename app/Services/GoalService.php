<?php

namespace App\Services;

use App\Models\Goal;

class GoalService
{
    /**
     * Add funds to a goal and return whether the goal is now completed.
     *
     * @param  Goal   $goal   The goal to fund.
     * @param  float  $amount The amount to add (must be > 0).
     * @return bool   True if the goal reached 100% after this operation.
     */
    public function addFunds(Goal $goal, float $amount): bool
    {
        $goal->increment('current_amount', $amount);
        $goal->refresh();

        return $this->isCompleted($goal);
    }

    /**
     * Determine whether a goal has been fully funded.
     */
    public function isCompleted(Goal $goal): bool
    {
        return $goal->target_amount > 0
            && $goal->current_amount >= $goal->target_amount;
    }

    /**
     * Calculate the forge/progress stats for a goal.
     * Mirrors the getForgeStats() function previously in Metas.vue.
     *
     * @param  Goal  $goal
     * @return array{current: float, target: float, percent: float, level: int, isCompleted: bool}
     */
    public function getForgeStats(Goal $goal): array
    {
        $target  = (float) $goal->target_amount;
        $current = min((float) $goal->current_amount, $target); // Cap at target (no overflow)

        $percent = $target > 0 ? ($current / $target) * 100 : 0.0;

        // RPG Levels: every 20% of progress = +1 level, capped at 5
        $level = min(5, (int) floor($percent / 20) + 1);

        return [
            'current'     => $current,
            'target'      => $target,
            'percent'     => $percent,
            'level'       => $level,
            'isCompleted' => $percent >= 100,
        ];
    }
}
