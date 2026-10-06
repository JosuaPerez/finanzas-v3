<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [\App\Http\Middleware\ConfigureMobileApi::class]);
        $middleware->validateCsrfTokens(except: ['api/mobile/v1/*']);
        $middleware->alias(['mobile-ability' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class, 'financial-submission' => \App\Http\Middleware\FinancialSubmission::class]);
        $middleware->web(append: [
            \App\Http\Middleware\ProtectWebResponses::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\NativeBackendGateway::class,
        ]);

        $middleware->prependToPriorityList(\Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, \App\Http\Middleware\NativeBackendGateway::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
