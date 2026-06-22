<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    /**
     * Determine if the user can view the goal.
     */
    public function view(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    /**
     * Determine if the user can add funds to the goal.
     */
    public function addFunds(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    /**
     * Determine if the user can delete the goal.
     */
    public function delete(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }
}
