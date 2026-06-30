<?php

namespace App\Jobs;

use App\Services\BpdExchangeRateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshExchangeRateJob implements ShouldQueue
{
    use Queueable;

    /**
     * Force-refresh the BPD exchange rate cache so the HTTP call
     * never blocks a user request inside DebtController::pay().
     */
    public function handle(BpdExchangeRateService $service): void
    {
        $service->forgetCachedRate();
        $service->getUsdSellRate();
    }
}
