<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                // Streak data — available as usePage().props.auth.current_streak
                'current_streak' => $request->user()?->current_streak ?? 0,
            ],
            // Flash data — toast system reads level_up and streak_bonus from here.
            'flash' => [
                'level_up'      => $request->session()->get('level_up'),
                'streak_bonus'  => $request->session()->get('streak_bonus'),
                'quest_claimed' => $request->session()->get('quest_claimed'),
            ],
        ];
    }
}
