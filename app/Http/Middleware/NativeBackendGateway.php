<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class NativeBackendGateway
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('nativephp-internal.running')) {
            return $next($request);
        }

        return app(HandleInertiaRequests::class)->handle($request, fn ($request) => $this->forward($request, $next));
    }

    private function forward(Request $request, Closure $next): Response
    {
        $path = '/'.$request->path();
        $token = $request->session()->get('mobile_token');
        if ($path === '/') {
            return redirect($token ? '/dashboard' : '/login');
        }
        $guestPages = ['/login', '/register', '/forgot-password', '/terminos'];
        if ($request->isMethod('get') && (in_array($path, $guestPages, true) || preg_match('#^/reset-password/[^/]+$#', $path))) {
            return $next($request);
        }
        $publicAction = $request->isMethod('post') && in_array($path, ['/login', '/register', '/forgot-password', '/reset-password'], true);
        $pages = ['/dashboard', '/presupuesto', '/deudas', '/metas', '/historial', '/profile'];
        $allowed = ($request->isMethod('get') && (in_array($path, $pages, true) || preg_match('#^/presupuestos/exportar(?:/[0-9]+)?$#', $path)))
            || ($request->isMethod('post') && (in_array($path, ['/presupuestos', '/quick-attack', '/deudas', '/metas', '/quests/claim', '/logout'], true) || preg_match('#^/(?:deudas/[0-9]+/pagar|metas/[0-9]+/add-funds)$#', $path)))
            || ($request->isMethod('patch') && in_array($path, ['/profile', '/profile/financial-preferences'], true))
            || ($request->isMethod('put') && $path === '/password')
            || ($request->isMethod('delete') && ($path === '/profile' || preg_match('#^/(?:deudas|metas)/[0-9]+$#', $path)));
        abort_unless($publicAction || $allowed, 404);
        if (! $publicAction && ! $token) {
            return redirect('/login');
        }
        $base = rtrim(config('mobile.backend_url'), '/');
        $parts = parse_url($base);
        abort_unless(($parts['scheme'] ?? null) === 'https' && ! empty($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']) && empty($parts['query']) && empty($parts['fragment']) && empty($parts['path']), 503);
        try {
            $client = Http::acceptJson()->withoutRedirecting()->connectTimeout(10)->timeout(30);
            if (! $publicAction) {
                $client = $client->withToken($token);
            }
            $response = $client->send($request->method(), $base.'/api/mobile/v1'.$path, ['json' => $request->except('_token')]);
        } catch (ConnectionException $exception) {
            return $this->unavailable($request);
        }
        if ($path === '/logout' || (! $publicAction && $response->status() === 401)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }
        if ($response->status() === 422) {
            throw ValidationException::withMessages($response->json('errors') ?: ['server' => 'Revisa los datos e inténtalo de nuevo.']);
        }
        if (! $response->successful()) {
            return $this->unavailable($request, $response->status() === 429 ? 'Espera un momento antes de volver a intentarlo.' : null);
        }
        if ($publicAction && in_array($path, ['/login', '/register'], true)) {
            $newToken = $response->json('token');
            if (! is_string($newToken) || $newToken === '') {
                return $this->unavailable($request);
            }
            $request->session()->regenerate();
            $request->session()->put('mobile_token', $newToken);

            return redirect('/dashboard');
        }
        if ($request->isMethod('get')) {
            if (str_starts_with($path, '/presupuestos/exportar')) {
                if (! str_contains($response->header('Content-Type'), 'spreadsheetml')) {
                    return $this->unavailable($request);
                }

                return response($response->body())->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->header('Content-Disposition', 'attachment; filename="presupuesto.xlsx"');
            }
            $component = $response->json('component');
            $props = $response->json('props');
            if (! in_array($component, ['Dashboard', 'Presupuesto', 'Deudas', 'Metas', 'Historial', 'Profile/Edit'], true) || ! is_array($props)) {
                return $this->unavailable($request);
            }
            unset($props['errors'], $props['flash']);
            $props['nativeRuntime'] = true;

            return Inertia::render($component, $props)->toResponse($request);
        }
        foreach (['success', 'status', 'level_up', 'streak_bonus', 'quest_claimed'] as $key) {
            if ($value = $response->json('flash.'.$key)) {
                $request->session()->flash($key, $value);
            }
        }
        if ($path === '/password' || ($path === '/profile' && $request->isMethod('delete'))) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }

        return $publicAction ? redirect('/login') : back();
    }

    private function unavailable(Request $request, ?string $message = null): Response
    {
        $message ??= 'No pudimos conectar con el servidor. Revisa tu conexión antes de volver a intentarlo.';
        if (! $request->isMethod('get')) {
            throw ValidationException::withMessages(['server' => $message, 'email' => $message]);
        }

        return Inertia::render('ConnectionError', ['message' => $message])->toResponse($request);
    }
}
