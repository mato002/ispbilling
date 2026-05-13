<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\ResolveLandingIspFromSubdomain::class,
        ]);

        $middleware->alias([
            'customer.session' => \App\Http\Middleware\CustomerSession::class,
            'validate.isp' => \App\Http\Middleware\ValidateIsp::class,
            'ensure.isp.platform' => \App\Http\Middleware\EnsureIspPlatformAccess::class,
            'ensure.saas.onboarding' => \App\Http\Middleware\EnsureSaasOnboardingComplete::class,
            'ensure.landing.isp' => \App\Http\Middleware\EnsureLandingIspPresent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
