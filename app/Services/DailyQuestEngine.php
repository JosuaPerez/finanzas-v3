<?php

namespace App\Services;

use App\Models\User;
use App\Models\Expense;
use App\Models\Debt;

class DailyQuestEngine
{
    /**
     * Evaluates the status of the 3 daily quests.
     */
    public function getStatus(User $user): array
    {
        // Quest 1: Log at least 1 expense today
        $q1 = Expense::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->exists();

        // Quest 2: Register at least 1 debt payment today (updated_at is bumped by applyPayment)
        $q2 = Debt::where('user_id', $user->id)
            ->whereDate('updated_at', today())
            ->exists();

        // Quest 3: Maintain an active streak > 0
        $q3 = $user->current_streak > 0;

        $completedCount = (int)$q1 + (int)$q2 + (int)$q3;
        $alreadyClaimed = $user->daily_reward_claimed_at?->isToday() ?? false;
        
        return [
            'list' => [
                [
                    'id' => 'q1',
                    'name' => 'Asegurar Suministros',
                    'description' => 'Registra al menos 1 ataque rápido (gasto) hoy.',
                    'completed' => $q1,
                    'xp' => 50,
                ],
                [
                    'id' => 'q2',
                    'name' => 'Ofensiva Táctica',
                    'description' => 'Realiza al menos 1 pago a una deuda hoy.',
                    'completed' => $q2,
                    'xp' => 50,
                ],
                [
                    'id' => 'q3',
                    'name' => 'Disciplina de Hierro',
                    'description' => 'Mantén una racha activa (mayor a 0).',
                    'completed' => $q3,
                    'xp' => 50,
                ],
            ],
            'completed_count' => $completedCount,
            'can_claim' => $completedCount > 0 && !$alreadyClaimed,
            'already_claimed' => $alreadyClaimed,
            'claimable_xp' => $completedCount * 50,
        ];
    }
}
