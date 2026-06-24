<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BpdExchangeRateService
{
    /**
     * Rate used when the API is unreachable or returns invalid data.
     * Update this constant to reflect a reasonable market default.
     */
    private const FALLBACK_RATE = 60.50;

    /** Cache key for the stored rate. */
    private const CACHE_KEY = 'bpd_usd_sell_rate';

    /** How many hours to keep a successful rate in cache before re-fetching. */
    private const CACHE_TTL_HOURS = 12;

    /**
     * Return the USD → DOP sell rate published by BPD.
     *
     * Successful responses are cached for 12 hours so the API is not hit on
     * every page load. The FALLBACK_RATE is returned (and NOT cached) on any
     * network error, timeout, or malformed response, so the next request will
     * try the live API again.
     */
    public function getUsdSellRate(): float
    {
        if (Cache::has(self::CACHE_KEY)) {
            return (float) Cache::get(self::CACHE_KEY);
        }

        $rate = $this->fetchFromApi();

        if ($rate !== self::FALLBACK_RATE) {
            Cache::put(self::CACHE_KEY, $rate, now()->addHours(self::CACHE_TTL_HOURS));
        }

        return $rate;
    }

    /**
     * Invalidate the cached rate so the next call hits the live API.
     * Useful if you need to force-refresh from a console command or admin panel.
     */
    public function forgetCachedRate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function fetchFromApi(): float
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-IBM-Client-Id'     => config('services.bpd.client_id'),
                    'X-IBM-Client-Secret' => config('services.bpd.client_secret'),
                    'Accept'              => 'application/json',
                ])
                ->get(config('services.bpd.base_url') . '/consultatasa/consultaTasa');

            if ($response->failed()) {
                Log::warning('BpdExchangeRateService: API respondió con error HTTP.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return self::FALLBACK_RATE;
            }

            // Expected shape: { "monedas": [ { "moneda": "USD", "compra": X, "venta": Y }, ... ] }
            $rate = (float) ($response->json('monedas.0.venta') ?? 0);

            if ($rate <= 0) {
                Log::warning('BpdExchangeRateService: Tasa USD inválida o cero recibida; usando fallback.', [
                    'response' => $response->json(),
                ]);
                return self::FALLBACK_RATE;
            }

            Log::info('BpdExchangeRateService: Tasa USD obtenida exitosamente.', [
                'rate' => $rate,
            ]);

            return $rate;

        } catch (\Throwable $e) {
            Log::error('BpdExchangeRateService: Excepción al consultar la API de BPD.', [
                'error' => $e->getMessage(),
            ]);
            return self::FALLBACK_RATE;
        }
    }
}
