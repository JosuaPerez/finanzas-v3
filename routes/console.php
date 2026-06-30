<?php

use App\Jobs\RefreshExchangeRateJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the BPD USD exchange rate cache permanently warm.
// TTL in BpdExchangeRateService is 12h — this job fires every 12h to refresh
// before expiry, so DebtController::pay() never blocks on the live API.
Schedule::job(new RefreshExchangeRateJob)->cron('0 */12 * * *');
