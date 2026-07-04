<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DailyQuestEngine;
use Illuminate\Http\RedirectResponse;

class QuestController extends Controller
{
    public function __construct(
        private readonly DailyQuestEngine $questEngine
    ) {}

    public function claim(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $questStatus = $this->questEngine->getStatus($user);
        
        if (!$questStatus['can_claim']) {
            return back()->with('error', 'No hay recompensas disponibles o ya fueron reclamadas hoy.');
        }

        // Award XP
        $xpToAward = $questStatus['claimable_xp'];
        $user->addXp($xpToAward);

        // Mark as claimed for today
        $user->daily_reward_claimed_at = today();
        $user->save();

        session()->flash('quest_claimed', [
            'xp' => $xpToAward,
            'count' => $questStatus['completed_count'],
        ]);

        return back();
    }
}
