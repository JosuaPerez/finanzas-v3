<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

it('uses local services in native runtime and preserves the device database path', function () {
    config([
        'nativephp-internal.running' => true,
        'database.default' => 'mysql',
        'database.connections.sqlite.database' => '/device/database/database.sqlite',
        'cache.default' => 'redis',
        'queue.default' => 'redis',
        'session.driver' => 'database',
    ]);

    (new AppServiceProvider(app()))->boot();

    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe('/device/database/database.sqlite')
        ->and(config('cache.default'))->toBe('file')
        ->and(config('queue.default'))->toBe('sync')
        ->and(config('session.driver'))->toBe('file');
});

it('keeps web service configuration and production https enforcement', function () {
    config(['nativephp-internal.running' => false, 'app.env' => 'production']);
    $keys = ['database.default', 'cache.default', 'queue.default', 'session.driver'];
    $before = array_map(fn ($key) => config($key), $keys);
    URL::forceScheme(null);

    (new AppServiceProvider(app()))->boot();

    expect(array_map(fn ($key) => config($key), $keys))->toBe($before)
        ->and(url('/login'))->toStartWith('https://');
    URL::forceScheme(null);
});

it('does not force web https inside the embedded production runtime', function () {
    config(['nativephp-internal.running' => true, 'app.env' => 'production']);
    URL::forceScheme(null);

    (new AppServiceProvider(app()))->boot();

    expect(url('/login'))->toStartWith('http://');
});

it('exposes runtime identity to the frontend and avoids remote fonts on native login', function () {
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('nativeRuntime', false))
        ->assertSee('fonts.bunny.net', false);

    config(['nativephp-internal.running' => true]);
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('nativeRuntime', true))
        ->assertDontSee('fonts.bunny.net', false);
});
