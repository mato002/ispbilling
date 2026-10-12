<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DeployMigrateController;
use App\Http\Controllers\DeploySyncController;
use App\Http\Controllers\ISP\CustomerController;
use App\Http\Controllers\ISP\PlanController;
use App\Http\Controllers\ISP\RouterController;
use App\Http\Controllers\ISP\BillingController;
use App\Http\Controllers\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public API endpoints (no auth required)
|
*/

// Public API status
Route::post('/deploy/sync-public', [DeploySyncController::class, 'syncPublic'])->middleware('throttle:5,1');
Route::post('/deploy/migrate', [DeployMigrateController::class, 'migrate'])->middleware('throttle:5,1');
Route::get('/status', function () {
    return response()->json([
        'status' => 'online',
        'service' => 'ISP Billing System',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.status');

// Public payment webhooks (no auth required)
Route::post('/payments/mpesa/callback/{webhook_token}', [PaymentController::class, 'mpesaCallback'])
    ->where('webhook_token', '[A-Za-z0-9]{32,128}')
    ->name('payments.mpesa.isp-callback');

Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback'])
    ->name('payments.mpesa.callback');
    
Route::post('/payments/mpesa/timeout', [PaymentController::class, 'mpesaTimeout'])
    ->name('payments.mpesa.timeout');

Route::post('/payments/mpesa/c2b/validation/{webhook_token}', [\App\Http\Controllers\MpesaC2BWebhookController::class, 'validation'])
    ->where('webhook_token', '[A-Za-z0-9]{32,128}')
    ->name('payments.mpesa.c2b.validation');

Route::post('/payments/mpesa/c2b/confirmation/{webhook_token}', [\App\Http\Controllers\MpesaC2BWebhookController::class, 'confirmation'])
    ->where('webhook_token', '[A-Za-z0-9]{32,128}')
    ->name('payments.mpesa.c2b.confirmation');

Route::post('/payments/selcom/callback', [PaymentController::class, 'selcomCallback'])
    ->name('payments.selcom.callback');
Route::post('/payments/stripe/webhook', [PaymentController::class, 'stripeWebhook'])
    ->name('payments.stripe.webhook');

// Public Hotspot API (for MikroTik router integration)
Route::prefix('hotspot')->name('hotspot.')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Hotspot\HotspotApiController::class, 'login'])->name('api.login');
    Route::post('/logout', [\App\Http\Controllers\Hotspot\HotspotApiController::class, 'logout'])->name('api.logout');
    Route::get('/status', [\App\Http\Controllers\Hotspot\HotspotApiController::class, 'status'])->name('api.status');
});

/*
|--------------------------------------------------------------------------
| Authenticated API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Tenant-scoped ISP API (platform lock + onboarding enforced like web console)
    Route::middleware(['load.tenant.isp', 'ensure.isp.platform', 'ensure.saas.onboarding'])->group(function () {
        Route::post('/payments/initiate', [PaymentController::class, 'initiate'])
            ->name('payments.initiate');

        Route::post('/payments/{payment}/confirm-manual', [PaymentController::class, 'confirmManual'])
            ->name('payments.confirm-manual');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/customer-status', [\App\Http\Controllers\ISP\Web\ReportController::class, 'customerStatusReport'])
                ->name('customer-status');
            Route::get('/revenue-per-plan', [\App\Http\Controllers\ISP\Web\ReportController::class, 'revenuePerPlanReport'])
                ->name('revenue-per-plan');
            Route::get('/monthly-collections', [\App\Http\Controllers\ISP\Web\ReportController::class, 'monthlyCollectionsReport'])
                ->name('monthly-collections');
        });

        Route::prefix('isp')->group(function () {
            Route::apiResource('customers', CustomerController::class);
            Route::post('/customers/{customer}/suspend', [CustomerController::class, 'suspend'])
                ->name('customers.suspend');
            Route::post('/customers/{customer}/activate', [CustomerController::class, 'activate'])
                ->name('customers.activate');

            Route::apiResource('plans', PlanController::class);

            Route::apiResource('routers', RouterController::class);

            Route::get('/billing/invoices', [BillingController::class, 'invoices'])
                ->name('billing.invoices');
            Route::get('/billing/payments', [BillingController::class, 'payments'])
                ->name('billing.payments');
            Route::post('/billing/generate-invoices', [BillingController::class, 'generateInvoices'])
                ->name('billing.generate-invoices');
            Route::get('/billing/stats', [BillingController::class, 'stats'])
                ->name('billing.stats');

            Route::get('/sessions', [\App\Http\Controllers\ISP\Web\SessionController::class, 'index'])
                ->name('sessions.index');
            Route::get('/sessions/{session}', [\App\Http\Controllers\ISP\Web\SessionController::class, 'show'])
                ->name('sessions.show');
            Route::post('/sessions/{session}/disconnect', [\App\Http\Controllers\ISP\Web\SessionController::class, 'disconnect'])
                ->name('sessions.disconnect');
            Route::get('/sessions/sync/{router}', function ($routerId) {
                $router = \App\Models\Router::findOrFail($routerId);
                $mikrotik = new \App\Services\MikroTikService($router);
                $synced = $mikrotik->syncActiveSessions();
                return response()->json(['synced' => $synced]);
            })->name('sessions.sync');
        });
    });

    // Admin routes (super admin only)
    Route::middleware(['role:super_admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
            ->name('admin.dashboard.stats');
        Route::get('/isps', [DashboardController::class, 'isps'])
            ->name('admin.isps');
    });
});
