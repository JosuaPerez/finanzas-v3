<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $nativeRuntime = (bool) config('nativephp-internal.running', false);

        if ($nativeRuntime) {
            // The embedded app has no Redis server or queue worker. Keep the
            // database path provided by NativePHP. Financial requests use the backend API.
            config([
                'database.default' => 'sqlite',
                'cache.default' => 'file',
                'session.driver' => 'file',
                'session.encrypt' => true,
                'session.lifetime' => (int) config('mobile.token_days', 30) * 1440,
                'session.expire_on_close' => false,
                'session.domain' => null,
                'session.secure' => false,
                'queue.default' => 'sync',
            ]);
        }

        Vite::prefetch(concurrency: 3);

        if (config('app.env') === 'production' && ! $nativeRuntime) {
            URL::forceScheme('https');
        }

        Gate::define('viewPulse', function (User $user) {
            return $user->email === env('PULSE_ADMIN_EMAIL');
        });
    }
}
