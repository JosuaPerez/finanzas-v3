<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FinancialSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = preg_replace('#^api/mobile/v1/#', '', $request->path());
        if ($request->isMethod('get') || ! preg_match('#^(?:presupuestos|quick-attack|deudas(?:/[0-9]+(?:/pagar)?)?|metas(?:/[0-9]+(?:/add-funds)?)?)$#', $path)) {
            return $next($request);
        }
        $request->validate(['request_id' => ['nullable', 'uuid']]);

        return DB::transaction(function () use ($request, $next, $path) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $user->withAccessToken($request->user()->currentAccessToken());
            auth()->setUser($user);
            $request->setUserResolver(fn () => $user);
            $id = $request->input('request_id');
            $operation = $request->method().':'.$path;
            $payload = $request->except(['request_id', '_token']);
            $sort = function (&$value) use (&$sort) {
                if (! is_array($value)) {
                    return;
                }
                foreach ($value as &$child) {
                    $sort($child);
                }
                ksort($value);
            };
            $sort($payload);
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            if ($id && ($stored = DB::table('financial_submissions')->where('request_id', $id)->first())) {
                if ($stored->user_id != $user->id || $stored->operation !== $operation || $stored->payload_hash !== $hash) {
                    throw ValidationException::withMessages(['request_id' => 'Este envío ya se procesó con otros datos. Revisa el historial antes de registrar otro movimiento.']);
                }
                $saved = json_decode($stored->response, true);
                foreach ($saved['flash'] as $key => $value) {
                    $request->session()->flash($key, $value);
                }

                return response($saved['body'], $saved['status'], $saved['headers']);
            }
            $response = $next($request);
            if ($id && $response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                $headers = [];
                foreach (['Location', 'Content-Type', 'X-Inertia'] as $key) {
                    if ($response->headers->has($key)) {
                        $headers[$key] = $response->headers->get($key);
                    }
                }
                $flash = [];
                foreach (['success', 'status', 'level_up', 'streak_bonus', 'quest_claimed'] as $key) {
                    if ($value = $request->session()->get($key)) {
                        $flash[$key] = $value;
                    }
                }
                DB::table('financial_submissions')->insert([
                    'user_id' => $user->id, 'request_id' => $id, 'operation' => $operation, 'payload_hash' => $hash,
                    'response' => json_encode(['body' => $response->getContent(), 'status' => $response->getStatusCode(), 'headers' => $headers, 'flash' => $flash], JSON_THROW_ON_ERROR),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $response;
        });
    }
}
