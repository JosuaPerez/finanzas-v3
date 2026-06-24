<?php

use App\Services\BpdExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
});

it('fetches and caches the USD sell rate on a successful BPD API response', function () {
    Http::fake([
        '*/consultatasa/consultaTasa' => Http::response([
            'monedas' => [
                [
                    'moneda' => 'USD',
                    'compra' => 58.00,
                    'venta'  => 59.75,
                ]
            ]
        ], 200)
    ]);

    $service = new BpdExchangeRateService();
    $rate = $service->getUsdSellRate();

    expect($rate)->toBe(59.75);
    expect(Cache::has('bpd_usd_sell_rate'))->toBeTrue();
});

it('returns the fallback rate of 60.50 and logs a warning on HTTP failure', function () {
    Http::fake([
        '*/consultatasa/consultaTasa' => Http::response([], 500)
    ]);

    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')->never();
    Log::shouldReceive('info')->never();

    $service = new BpdExchangeRateService();
    $rate = $service->getUsdSellRate();

    expect($rate)->toBe(60.50);
    expect(Cache::has('bpd_usd_sell_rate'))->toBeFalse();
});

it('returns the fallback rate of 60.50 and logs an error on exception or timeout', function () {
    Http::fake([
        '*/consultatasa/consultaTasa' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Timeout')
    ]);

    Log::shouldReceive('error')->once();
    Log::shouldReceive('warning')->never();
    Log::shouldReceive('info')->never();

    $service = new BpdExchangeRateService();
    $rate = $service->getUsdSellRate();

    expect($rate)->toBe(60.50);
    expect(Cache::has('bpd_usd_sell_rate'))->toBeFalse();
});

it('returns the fallback rate of 60.50 when the API returns an invalid or zero rate', function () {
    Http::fake([
        '*/consultatasa/consultaTasa' => Http::response([
            'monedas' => [
                [
                    'moneda' => 'USD',
                    'compra' => 58.00,
                    'venta'  => 0,
                ]
            ]
        ], 200)
    ]);

    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')->never();
    Log::shouldReceive('info')->never();

    $service = new BpdExchangeRateService();
    $rate = $service->getUsdSellRate();

    expect($rate)->toBe(60.50);
    expect(Cache::has('bpd_usd_sell_rate'))->toBeFalse();
});

it('successfully flushes the cached rate using forgetCachedRate', function () {
    Cache::put('bpd_usd_sell_rate', 59.20, now()->addHours(12));
    expect(Cache::has('bpd_usd_sell_rate'))->toBeTrue();

    $service = new BpdExchangeRateService();
    $service->forgetCachedRate();

    expect(Cache::has('bpd_usd_sell_rate'))->toBeFalse();
});
