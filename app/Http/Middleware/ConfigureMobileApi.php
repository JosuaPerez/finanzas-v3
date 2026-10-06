<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConfigureMobileApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/mobile/v1/*')) {
            return $next($request);
        }
        if (config('nativephp-internal.running')) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $original = [
            'session.driver' => config('session.driver'),
            'sanctum.guard' => config('sanctum.guard'),
            'auth.defaults.guard' => config('auth.defaults.guard'),
        ];
        config(['session.driver' => 'array', 'sanctum.guard' => []]);
        app('session')->forgetDrivers();
        Auth::forgetGuards();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Inertia', 'true');

        try {
            $response = $next($request);
            // Reuse web actions and their validation, but do not follow redirects
            // or authenticate this stateless API through browser cookies.
            if ($response->isRedirection()) {
                $flash = collect(['success', 'status', 'level_up', 'streak_bonus', 'quest_claimed'])
                    ->mapWithKeys(fn ($key) => [$key => $request->session()->get($key)])->filter()->all();
                $response = response()->json(['flash' => $flash]);
            }
            $response->headers->remove('Set-Cookie');
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            return $response;
        } finally {
            config($original);
            Auth::shouldUse($original['auth.defaults.guard']);
            Auth::forgetGuards();
            app('session')->forgetDrivers();
        }
    }
}
