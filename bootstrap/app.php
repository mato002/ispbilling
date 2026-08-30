<?php

use App\Http\Middleware\CustomerSession;
use App\Http\Middleware\HandleModalFormResponse;
use App\Http\Middleware\HandleTurboFrameResponse;
use App\Http\Middleware\EnsureIspPermission;
use App\Http\Middleware\EnsureIspPlatformAccess;
use App\Http\Middleware\EnsureIspWorkspaceAccess;
use App\Http\Middleware\EnsureLandingIspPresent;
use App\Http\Middleware\EnsureSaasOnboardingComplete;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\LoadTenantIsp;
use App\Http\Middleware\ResolveLandingIspFromSubdomain;
use App\Http\Middleware\ShareNotifications;
use App\Http\Middleware\ValidateIsp;
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
            ResolveLandingIspFromSubdomain::class,
        ]);

        $middleware->web(append: [
            ShareNotifications::class,
            HandleModalFormResponse::class,
            HandleTurboFrameResponse::class,
        ]);

        $middleware->alias([
            'customer.session' => CustomerSession::class,
            'validate.isp' => ValidateIsp::class,
            'ensure.isp.platform' => EnsureIspPlatformAccess::class,
            'ensure.isp.workspace' => EnsureIspWorkspaceAccess::class,
            'ensure.isp.can' => EnsureIspPermission::class,
            'ensure.isp.operator' => \App\Http\Middleware\EnsureIspWorkspaceOperator::class,
            'ensure.isp.technician' => \App\Http\Middleware\EnsureIspTechnician::class,
            'ensure.saas.onboarding' => EnsureSaasOnboardingComplete::class,
            'ensure.landing.isp' => EnsureLandingIspPresent::class,
            'ensure.superadmin' => EnsureSuperAdmin::class,
            'load.tenant.isp' => LoadTenantIsp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
