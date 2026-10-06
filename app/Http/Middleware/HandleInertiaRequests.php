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
        return $request->is('api/mobile/v1/*') ? null : parent::version($request);
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
            'nativeRuntime' => (bool) config('nativephp-internal.running', false),
            'movementRequestId' => fn () => (string) \Illuminate\Support\Str::uuid(),
            'auth' => [
                'user' => $request->user(),
                // Streak data — available as usePage().props.auth.current_streak
                'current_streak' => $request->user()?->current_streak ?? 0,
            ],
            // Flash data — toast system reads level_up and streak_bonus from here.
            'finance' => [
                'currencies' => config('finance.currencies'),
                'locales' => config('finance.locales'),
            ],
            'movementDebts' => fn () => $request->user()
                ? $request->user()->debts()->where('balance', '>', 0)->get(['id', 'name', 'balance', 'currency', 'minimum_payment'])
                : [],
            'movementBudget' => function () use ($request) {
                $budget = $request->user()
                    ? \App\Models\Budget::where('user_id', $request->user()->id)->latest('id')->first()
                    : null;
                if (! $budget) return null;
                $details = is_string($budget->details) ? json_decode($budget->details, true) : $budget->details;
                return ['currency' => $budget->currency, 'remaining' => $details['remaining'] ?? null];
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'level_up'      => $request->session()->get('level_up'),
                'streak_bonus'  => $request->session()->get('streak_bonus'),
                'quest_claimed' => $request->session()->get('quest_claimed'),
            ],
        ];
    }
}
