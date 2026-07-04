<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\ChecksAchievements;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Evaluates achievements asynchronously so the HTTP request
 * returns immediately and the DB-heavy checks run in a worker.
 *
 * Dispatch:  EvaluateAchievementsJob::dispatch($user, 'event_name');
 */
class EvaluateAchievementsJob implements ShouldQueue
{
    use Queueable, ChecksAchievements;

    public function __construct(
        public readonly User   $user,
        public readonly string $event,
    ) {}

    public function handle(): void
    {
        $this->checkAchievements($this->user, $this->event);
    }
}
