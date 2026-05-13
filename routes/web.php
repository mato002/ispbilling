<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('welcome');

// Static Pages
Route::get('/terms', [\App\Http\Controllers\StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [\App\Http\Controllers\StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/documentation', [\App\Http\Controllers\StaticPageController::class, 'documentation'])->name('documentation');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isSuperAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    // ISP admin/staff will use the enhanced dashboard
    if ($user->isp_id) {
        return redirect()->route('isp.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ISP SaaS onboarding (no ensure.saas.onboarding — completes plan + billing before console)
    Route::middleware('ensure.isp.platform')->prefix('isp')->name('isp.')->group(function () {
        Route::get('/saas-onboarding', [\App\Http\Controllers\ISP\Web\SaasOnboardingController::class, 'show'])->name('saas-onboarding.show');
        Route::post('/saas-onboarding', [\App\Http\Controllers\ISP\Web\SaasOnboardingController::class, 'store'])->name('saas-onboarding.store');
    });

    // ISP Dashboard Routes (for ISP Admin and Staff)
    Route::middleware(['ensure.isp.platform', 'ensure.saas.onboarding'])->prefix('isp')->name('isp.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\ISP\Web\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/platform-subscription', [\App\Http\Controllers\ISP\Web\PlatformSubscriptionController::class, 'index'])->name('platform-subscription.index');
        Route::post('/platform-subscription/invoices/{invoice}/pay', [\App\Http\Controllers\ISP\Web\PlatformSubscriptionController::class, 'payInvoice'])->name('platform-subscription.pay');
        Route::get('/platform-subscription/invoices/{invoice}', [\App\Http\Controllers\ISP\Web\PlatformSubscriptionController::class, 'downloadInvoice'])->name('platform-subscription.invoice');

        // Customers
        Route::get('/customers', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{customer}/suspend', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'suspend'])->name('customers.suspend');
        Route::post('/customers/{customer}/activate', [\App\Http\Controllers\ISP\Web\CustomerController::class, 'activate'])->name('customers.activate');

        // Plans
        Route::get('/plans', [\App\Http\Controllers\ISP\Web\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [\App\Http\Controllers\ISP\Web\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [\App\Http\Controllers\ISP\Web\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}', [\App\Http\Controllers\ISP\Web\PlanController::class, 'show'])->name('plans.show');
        Route::get('/plans/{plan}/edit', [\App\Http\Controllers\ISP\Web\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Http\Controllers\ISP\Web\PlanController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/activate', [\App\Http\Controllers\ISP\Web\PlanController::class, 'activate'])->name('plans.activate');
        Route::post('/plans/{plan}/deactivate', [\App\Http\Controllers\ISP\Web\PlanController::class, 'deactivate'])->name('plans.deactivate');
        Route::post('/plans/{plan}/toggle-active', [\App\Http\Controllers\ISP\Web\PlanController::class, 'toggleActive'])->name('plans.toggle-active');

        // IP Pools
        Route::prefix('ip-pools')->name('ip-pools.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'store'])->name('store');
            Route::post('/sync', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'sync'])->name('sync');
            Route::get('/available', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'getAvailable'])->name('available');
            Route::get('/{ipPool}', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'show'])->name('show');
            Route::get('/{ipPool}/edit', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'edit'])->name('edit');
            Route::put('/{ipPool}', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'update'])->name('update');
            Route::delete('/{ipPool}', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'destroy'])->name('destroy');
            Route::post('/{ipPool}/activate', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'activate'])->name('activate');
            Route::post('/{ipPool}/deactivate', [\App\Http\Controllers\ISP\Web\IpPoolController::class, 'deactivate'])->name('deactivate');
        });

        // Active Users Monitoring
        Route::prefix('active-users')->name('active-users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ISP\Web\ActiveUsersController::class, 'index'])->name('index');
            Route::get('/realtime', [\App\Http\Controllers\ISP\Web\ActiveUsersController::class, 'getRealTimeUsers'])->name('realtime');
            Route::post('/disconnect', [\App\Http\Controllers\ISP\Web\ActiveUsersController::class, 'disconnectUser'])->name('disconnect');
            Route::get('/stats', [\App\Http\Controllers\ISP\Web\ActiveUsersController::class, 'getUserStats'])->name('stats');
        });

        // User Connection Management
        Route::prefix('user-connections')->name('user-connections.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'index'])->name('index');
            Route::post('/run-automatic', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'runAutomatic'])->name('run-automatic');
            Route::get('/statistics', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'getStatistics'])->name('statistics');
            Route::post('/batch', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'batchOperation'])->name('batch');
            Route::post('/{customer}/disconnect', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'disconnect'])->name('disconnect');
            Route::post('/{customer}/suspend', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'suspend'])->name('suspend');
            Route::post('/{customer}/reconnect', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'reconnect'])->name('reconnect');
            Route::get('/{customer}/status', [\App\Http\Controllers\ISP\Web\UserConnectionController::class, 'getStatus'])->name('status');
        });

        // Bandwidth Profiles
        Route::prefix('bandwidth-profiles')->name('bandwidth-profiles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'store'])->name('store');
            Route::get('/available', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'getAvailable'])->name('available');
            Route::get('/{bandwidthProfile}', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'show'])->name('show');
            Route::get('/{bandwidthProfile}/edit', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'edit'])->name('edit');
            Route::put('/{bandwidthProfile}', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'update'])->name('update');
            Route::delete('/{bandwidthProfile}', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'destroy'])->name('destroy');
            Route::post('/{bandwidthProfile}/activate', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'activate'])->name('activate');
            Route::post('/{bandwidthProfile}/deactivate', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'deactivate'])->name('deactivate');
            Route::post('/{bandwidthProfile}/deploy', [\App\Http\Controllers\ISP\Web\BandwidthProfileController::class, 'deploy'])->name('deploy');
        });

        // Hotspot Servers
        Route::prefix('hotspot-servers')->name('hotspot-servers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'index'])->name('index');
            Route::post('/sync', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'sync'])->name('sync');
            Route::get('/available', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'getAvailable'])->name('available');
            Route::get('/{hotspotServer}', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'show'])->name('show');
            Route::get('/{hotspotServer}/edit', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'edit'])->name('edit');
            Route::put('/{hotspotServer}', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'update'])->name('update');
            Route::delete('/{hotspotServer}', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'destroy'])->name('destroy');
            Route::post('/{hotspotServer}/activate', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'activate'])->name('activate');
            Route::post('/{hotspotServer}/deactivate', [\App\Http\Controllers\ISP\Web\HotspotServerController::class, 'deactivate'])->name('deactivate');
        });

        // Routers
        Route::get('/routers', [\App\Http\Controllers\ISP\Web\RouterController::class, 'index'])->name('routers.index');
        Route::get('/routers/export.csv', [\App\Http\Controllers\ISP\Web\RouterController::class, 'exportCsv'])->name('routers.export.csv');
        Route::post('/routers/bulk', [\App\Http\Controllers\ISP\Web\RouterController::class, 'bulk'])->name('routers.bulk');
        Route::get('/routers/create', [\App\Http\Controllers\ISP\Web\RouterController::class, 'create'])->name('routers.create');
        Route::post('/routers/test-connection', [\App\Http\Controllers\ISP\Web\RouterController::class, 'testConnection'])->name('routers.test-connection');
        Route::post('/routers', [\App\Http\Controllers\ISP\Web\RouterController::class, 'store'])->name('routers.store');
        Route::get('/routers/{router}', [\App\Http\Controllers\ISP\Web\RouterController::class, 'show'])->name('routers.show');
        Route::get('/routers/{router}/edit', [\App\Http\Controllers\ISP\Web\RouterController::class, 'edit'])->name('routers.edit');
        Route::put('/routers/{router}', [\App\Http\Controllers\ISP\Web\RouterController::class, 'update'])->name('routers.update');
        Route::post('/routers/{router}/activate', [\App\Http\Controllers\ISP\Web\RouterController::class, 'activate'])->name('routers.activate');
        Route::post('/routers/{router}/deactivate', [\App\Http\Controllers\ISP\Web\RouterController::class, 'deactivate'])->name('routers.deactivate');
        Route::post('/routers/{router}/test', [\App\Http\Controllers\ISP\Web\RouterController::class, 'testExistingConnection'])->name('routers.test');
        Route::post('/routers/{router}/poll', [\App\Http\Controllers\ISP\Web\RouterController::class, 'poll'])->name('routers.poll');
        Route::post('/routers/{router}/reboot', [\App\Http\Controllers\ISP\Web\RouterController::class, 'reboot'])->name('routers.reboot');
        Route::post('/routers/{router}/ping', [\App\Http\Controllers\ISP\Web\RouterController::class, 'ping'])->name('routers.ping');
        Route::post('/routers/{router}/backup', [\App\Http\Controllers\ISP\Web\RouterController::class, 'backup'])->name('routers.backup');
        Route::get('/routers/{router}/backups/{backup}/download', [\App\Http\Controllers\ISP\Web\RouterController::class, 'downloadBackup'])->name('routers.backups.download');

        // Subscriptions
        Route::get('/subscriptions', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/subscriptions', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('/subscriptions/{subscription}/renew', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::post('/subscriptions/{subscription}/cancel', [\App\Http\Controllers\ISP\Web\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Invoices
        Route::get('/invoices', [\App\Http\Controllers\ISP\Web\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\ISP\Web\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/customers/{customer}/invoices/create', [\App\Http\Controllers\ISP\Web\InvoiceController::class, 'createForCustomer'])->name('invoices.create-for-customer');
        Route::post('/customers/{customer}/invoices', [\App\Http\Controllers\ISP\Web\InvoiceController::class, 'store'])->name('invoices.store');

        // Payments
        Route::get('/payments', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/initiate', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'create'])->name('payments.initiate');
        Route::post('/payments/initiate', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'initiate'])->name('payments.initiate.store');
        Route::get('/payments/transaction-logs', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'transactionLogs'])->name('payments.transaction-logs');
        Route::get('/payments/transaction-logs/{log}', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'showTransactionLog'])->name('payments.transaction-log.show');
        Route::post('/payments/{payment}/activate-internet', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'activateInternet'])->name('payments.activate-internet');
        Route::get('/payments/{payment}', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/status', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'checkStatus'])->name('payments.status');
        Route::get('/customers/{customer}/payments', [\App\Http\Controllers\ISP\Web\PaymentController::class, 'customerPayments'])->name('payments.customer');

        // Reports
        Route::get('/reports', [\App\Http\Controllers\ISP\Web\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/customer-status', [\App\Http\Controllers\ISP\Web\ReportController::class, 'customerStatusReport'])->name('reports.customer-status');
        Route::get('/reports/revenue-per-plan', [\App\Http\Controllers\ISP\Web\ReportController::class, 'revenuePerPlanReport'])->name('reports.revenue-per-plan');
        Route::get('/reports/monthly-collections', [\App\Http\Controllers\ISP\Web\ReportController::class, 'monthlyCollectionsReport'])->name('reports.monthly-collections');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/recent', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'recent'])->name('notifications.recent');
        Route::post('/notifications', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'store'])->name('notifications.store');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/notifications/stats', [\App\Http\Controllers\ISP\Web\NotificationController::class, 'stats'])->name('notifications.stats');

        // Vouchers
        Route::get('/vouchers', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/vouchers/bulk/create', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'createBulk'])->name('vouchers.create-bulk');
        Route::post('/vouchers/bulk', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'storeBulk'])->name('vouchers.store-bulk');
        Route::get('/vouchers/{voucher}', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{voucher}/activate', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'activate'])->name('vouchers.activate');
        Route::delete('/vouchers/{voucher}', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/export', [\App\Http\Controllers\ISP\Web\VoucherController::class, 'export'])->name('vouchers.export');

        // Settings
        Route::get('/settings', [\App\Http\Controllers\ISP\Web\SettingController::class, 'index'])->name('settings.index');
        Route::get('/settings/company', [\App\Http\Controllers\ISP\Web\SettingController::class, 'company'])->name('settings.company');
        Route::post('/settings/company', [\App\Http\Controllers\ISP\Web\SettingController::class, 'updateCompany'])->name('settings.company.update');
        Route::get('/settings/custom-fields', [\App\Http\Controllers\ISP\Web\SettingController::class, 'customFields'])->name('settings.custom-fields');
        Route::post('/settings/custom-fields', [\App\Http\Controllers\ISP\Web\SettingController::class, 'updateCustomFields'])->name('settings.custom-fields.update');

        // Payment Method (MPESA Daraja) - CRUD
        Route::get('/settings/payment', [\App\Http\Controllers\ISP\Web\PaymentMethodController::class, 'index'])->name('settings.payment');
        Route::post('/settings/payment', [\App\Http\Controllers\ISP\Web\PaymentMethodController::class, 'store'])->name('settings.payment.store');
        Route::post('/settings/payment/{paymentMethod}/toggle', [\App\Http\Controllers\ISP\Web\PaymentMethodController::class, 'toggleActive'])->name('settings.payment.toggle');
        Route::delete('/settings/payment/{paymentMethod}', [\App\Http\Controllers\ISP\Web\PaymentMethodController::class, 'destroy'])->name('settings.payment.destroy');

        // Payment Settings - Multiple Payment Methods
        Route::get('/payment-settings', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'index'])->name('payment-settings.index');
        Route::get('/payment-settings/create', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'create'])->name('payment-settings.create');
        Route::post('/payment-settings', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'store'])->name('payment-settings.store');
        Route::get('/payment-settings/{paymentMethod}/edit', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
        Route::put('/payment-settings/{paymentMethod}', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'update'])->name('payment-settings.update');
        Route::delete('/payment-settings/{paymentMethod}', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'destroy'])->name('payment-settings.destroy');
        Route::post('/payment-settings/{paymentMethod}/toggle', [\App\Http\Controllers\ISP\Web\PaymentSettingsController::class, 'toggle'])->name('payment-settings.toggle');

        // Legacy payment gateways route (kept for backward compatibility)
        Route::get('/settings/payment-gateways', [\App\Http\Controllers\ISP\Web\SettingController::class, 'paymentGateways'])->name('settings.payment-gateways');
        Route::post('/settings/payment-gateways', [\App\Http\Controllers\ISP\Web\SettingController::class, 'updatePaymentGateways'])->name('settings.payment-gateways.update');

        Route::get('/settings/notifications', [\App\Http\Controllers\ISP\Web\SettingController::class, 'notifications'])->name('settings.notifications');
        Route::post('/settings/notifications', [\App\Http\Controllers\ISP\Web\SettingController::class, 'updateNotifications'])->name('settings.notifications.update');
        Route::get('/settings/users', [\App\Http\Controllers\ISP\Web\SettingController::class, 'users'])->name('settings.users');
        Route::get('/settings/billing', [\App\Http\Controllers\ISP\Web\SettingController::class, 'billing'])->name('settings.billing');
        Route::post('/settings/billing', [\App\Http\Controllers\ISP\Web\SettingController::class, 'updateBilling'])->name('settings.billing.update');

        // Hotspot Sessions Management
        Route::get('/sessions', [\App\Http\Controllers\ISP\Web\SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session}', [\App\Http\Controllers\ISP\Web\SessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/disconnect', [\App\Http\Controllers\ISP\Web\SessionController::class, 'disconnect'])->name('sessions.disconnect');
        Route::get('/customers/{customer}/sessions', [\App\Http\Controllers\ISP\Web\SessionController::class, 'customerHistory'])->name('sessions.customer-history');

        // PrimeBilling helpdesk (ISP → platform / Super Admin)
        Route::get('/helpdesk', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'index'])->name('helpdesk.index');
        Route::get('/helpdesk/create', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'create'])->name('helpdesk.create');
        Route::post('/helpdesk', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'store'])->name('helpdesk.store');
        Route::get('/helpdesk/{ticket}', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'show'])->name('helpdesk.show');
        Route::put('/helpdesk/{ticket}', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'update'])->name('helpdesk.update');
        Route::post('/helpdesk/{ticket}/reply', [\App\Http\Controllers\ISP\Web\HelpdeskController::class, 'reply'])->name('helpdesk.reply');

        // Support Tickets
        Route::get('/tickets', [\App\Http\Controllers\ISP\Web\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [\App\Http\Controllers\ISP\Web\TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [\App\Http\Controllers\ISP\Web\TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\ISP\Web\TicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}', [\App\Http\Controllers\ISP\Web\TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\ISP\Web\TicketController::class, 'reply'])->name('tickets.reply');

        // Activity & Audit Logs
        Route::get('/logs/activity', [\App\Http\Controllers\ISP\Web\ActivityLogController::class, 'index'])->name('logs.activity.index');
        Route::get('/logs/activity/export/csv', [\App\Http\Controllers\ISP\Web\ActivityLogController::class, 'exportCsv'])->name('logs.activity.export.csv');
        Route::get('/logs/activity/export/pdf', [\App\Http\Controllers\ISP\Web\ActivityLogController::class, 'exportPdf'])->name('logs.activity.export.pdf');
        Route::get('/logs/activity/{activityLog}', [\App\Http\Controllers\ISP\Web\ActivityLogController::class, 'show'])->name('logs.activity.show');
        Route::get('/logs/router-actions', [\App\Http\Controllers\ISP\Web\RouterActionLogController::class, 'index'])->name('logs.router-actions.index');
        Route::get('/logs/router-actions/{routerActionLog}', [\App\Http\Controllers\ISP\Web\RouterActionLogController::class, 'show'])->name('logs.router-actions.show');
    });
});

// Hotspot Portal Routes (Public - No Auth Required)
Route::prefix('hotspot')->name('hotspot.')->group(function () {
    Route::get('/packages', [\App\Http\Controllers\Hotspot\PortalController::class, 'packages'])->name('packages');
    Route::get('/splash', [\App\Http\Controllers\Hotspot\PortalController::class, 'splash'])->name('splash');
    Route::get('/login', [\App\Http\Controllers\Hotspot\PortalController::class, 'login'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Hotspot\PortalController::class, 'authenticate'])->name('authenticate');
    Route::get('/status', [\App\Http\Controllers\Hotspot\PortalController::class, 'status'])->name('status');
    Route::post('/logout', [\App\Http\Controllers\Hotspot\PortalController::class, 'logout'])->name('logout');
    Route::get('/redeem', [\App\Http\Controllers\Hotspot\PortalController::class, 'redeemVoucher'])->name('redeem');
    Route::post('/redeem', [\App\Http\Controllers\Hotspot\PortalController::class, 'processVoucher'])->name('process-voucher');

    // Direct package purchase from hotspot
    Route::get('/buy/{plan}', [\App\Http\Controllers\Hotspot\PortalController::class, 'buyPackage'])->name('buy-package');
    Route::post('/buy/{plan}', [\App\Http\Controllers\Hotspot\PortalController::class, 'processPurchase'])->name('process-purchase');

    // Customer checkout access (after successful payment)
    Route::get('/checkout/access/{payment}', [\App\Http\Controllers\Customer\CheckoutController::class, 'access'])
        ->name('checkout.access');
});

// Customer Self-Service Portal Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Customer\SelfServiceController::class, 'login'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Customer\SelfServiceController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [\App\Http\Controllers\Customer\SelfServiceController::class, 'logout'])->name('logout');

    Route::middleware('ensure.landing.isp')->group(function () {
        Route::get('/register', [\App\Http\Controllers\Customer\SelfServiceController::class, 'register'])->name('register');
        Route::post('/register', [\App\Http\Controllers\Customer\SelfServiceController::class, 'registerStore'])->name('register.store');
    });

    // Captive portal route (public, validates ISP via middleware)
    Route::get('/portal', [\App\Http\Controllers\Customer\PortalController::class, 'portal'])
        ->middleware('validate.isp')
        ->name('portal');

    // Checkout page (public, can be accessed with customer ID)
    Route::get('/checkout', [\App\Http\Controllers\Customer\SelfServiceController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/select-plan', [\App\Http\Controllers\Customer\SelfServiceController::class, 'selectPlan'])->name('checkout.select-plan');

    // Public payment initiation for checkout (no auth required)
    Route::post('/payments/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate'])->name('payments.initiate');

    Route::middleware('customer.session')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Customer\SelfServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/usage', [\App\Http\Controllers\Customer\SelfServiceController::class, 'usage'])->name('usage');
        Route::get('/invoices', [\App\Http\Controllers\Customer\SelfServiceController::class, 'invoices'])->name('invoices');
        Route::get('/sessions', [\App\Http\Controllers\Customer\SelfServiceController::class, 'sessions'])->name('sessions');
        Route::post('/change-password', [\App\Http\Controllers\Customer\SelfServiceController::class, 'changePassword'])->name('change-password');
    });
});

// Customer Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');

    // Plans
    Route::get('/plans', [\App\Http\Controllers\Customer\PlanController::class, 'index'])->name('plans');
    Route::get('/plans/{plan}', [\App\Http\Controllers\Customer\PlanController::class, 'show'])->name('plans.show');

    // Payments
    Route::get('/checkout', [\App\Http\Controllers\Customer\PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('/payment/process', [\App\Http\Controllers\Customer\PaymentController::class, 'processPayment'])->name('payment.process');
    Route::get('/payment/processing', [\App\Http\Controllers\Customer\PaymentController::class, 'processing'])->name('payment.processing');
    Route::post('/payment/status/check', [\App\Http\Controllers\Customer\PaymentController::class, 'checkStatus'])->name('payment.status.check');
    Route::get('/payment/success', [\App\Http\Controllers\Customer\PaymentController::class, 'success'])->name('payment.success');

    // Payment Callbacks
    Route::post('/payment/callback/{provider}', [\App\Http\Controllers\Customer\PaymentController::class, 'callback'])->name('payment.callback')->middleware(\App\Http\Middleware\HandlePaymentWebhook::class);
});

// Admin Routes - Check is done in controllers
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // ISP Management
    Route::get('/isps', [\App\Http\Controllers\Admin\ISPController::class, 'index'])->name('isps.index');
    Route::get('/isps/create', [\App\Http\Controllers\Admin\ISPController::class, 'create'])->name('isps.create');
    Route::post('/isps', [\App\Http\Controllers\Admin\ISPController::class, 'store'])->name('isps.store');
    Route::get('/isps/{isp}', [\App\Http\Controllers\Admin\ISPController::class, 'show'])->name('isps.show');
    Route::get('/isps/{isp}/edit', [\App\Http\Controllers\Admin\ISPController::class, 'edit'])->name('isps.edit');
    Route::put('/isps/{isp}', [\App\Http\Controllers\Admin\ISPController::class, 'update'])->name('isps.update');
    Route::post('/isps/{isp}/force-suspend-network', [\App\Http\Controllers\Admin\ISPController::class, 'forceSuspendNetwork'])->name('isps.force-suspend-network');
    Route::delete('/isps/{isp}', [\App\Http\Controllers\Admin\ISPController::class, 'destroy'])->name('isps.destroy');

    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');

    Route::get('/finance', [\App\Http\Controllers\Admin\PlatformFinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/commission', [\App\Http\Controllers\Admin\PlatformFinanceController::class, 'updateCommission'])->name('finance.commission');
    Route::post('/finance/payouts', [\App\Http\Controllers\Admin\PlatformFinanceController::class, 'storePayout'])->name('finance.payouts.store');
    Route::put('/finance/payouts/{payout}', [\App\Http\Controllers\Admin\PlatformFinanceController::class, 'updatePayoutStatus'])->name('finance.payouts.update');

    Route::resource('platform-saas-plans', \App\Http\Controllers\Admin\PlatformSaasPlanController::class)->except(['show']);

    Route::get('/audit/activity', [\App\Http\Controllers\Admin\PlatformAuditController::class, 'activity'])->name('audit.activity');
    Route::get('/audit/activity/{activityLog}', [\App\Http\Controllers\Admin\PlatformAuditController::class, 'showActivity'])->name('audit.activity.show');
    Route::get('/audit/security', [\App\Http\Controllers\Admin\PlatformAuditController::class, 'security'])->name('audit.security');

    Route::get('/network', [\App\Http\Controllers\Admin\PlatformNetworkController::class, 'index'])->name('network.index');
    Route::post('/network/dispatch', [\App\Http\Controllers\Admin\PlatformNetworkController::class, 'dispatchMaintenance'])->name('network.dispatch');
    Route::post('/network/routers/{router}/reset-circuit', [\App\Http\Controllers\Admin\PlatformNetworkController::class, 'resetRouterCircuit'])->name('network.routers.reset-circuit');

    Route::get('/system-health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('system-health.index');

    Route::get('/intelligence', [\App\Http\Controllers\Admin\PlatformIntelligenceController::class, 'index'])->name('intelligence.index');

    Route::get('/marketing', [\App\Http\Controllers\Admin\PlatformMarketingController::class, 'index'])->name('marketing.index');
    Route::post('/marketing', [\App\Http\Controllers\Admin\PlatformMarketingController::class, 'update'])->name('marketing.update');

    Route::get('/promo-codes', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::get('/promo-codes/create', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'create'])->name('promo-codes.create');
    Route::post('/promo-codes', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::get('/promo-codes/{globalPromoCode}/edit', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'edit'])->name('promo-codes.edit');
    Route::put('/promo-codes/{globalPromoCode}', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::delete('/promo-codes/{globalPromoCode}', [\App\Http\Controllers\Admin\GlobalPromoCodeController::class, 'destroy'])->name('promo-codes.destroy');

    Route::get('/integrations', [\App\Http\Controllers\Admin\IntegrationsController::class, 'index'])->name('integrations.index');
    Route::put('/integrations', [\App\Http\Controllers\Admin\IntegrationsController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/vault/clear', [\App\Http\Controllers\Admin\IntegrationsController::class, 'clearVaultKey'])->name('integrations.vault.clear');

    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/clear-cache', [\App\Http\Controllers\Admin\SettingController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/optimize', [\App\Http\Controllers\Admin\SettingController::class, 'optimize'])->name('settings.optimize');

    // ISP → platform helpdesk queue
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HelpdeskTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [\App\Http\Controllers\Admin\HelpdeskTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [\App\Http\Controllers\Admin\HelpdeskTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/reply', [\App\Http\Controllers\Admin\HelpdeskTicketController::class, 'reply'])->name('reply');
    });

    Route::resource('announcements', \App\Http\Controllers\Admin\SystemAnnouncementController::class)->except(['show']);

    // Hotspot Portal Settings
    Route::get('/portal-settings', [\App\Http\Controllers\Admin\PortalSettingController::class, 'index'])->name('portal-settings.index');
    Route::put('/portal-settings', [\App\Http\Controllers\Admin\PortalSettingController::class, 'update'])->name('portal-settings.update');
});

// Temporary cache clear routes - remove after use
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('route:cache');

    return 'All caches cleared successfully!';
});

Route::get('/clear-routes', function () {
    Artisan::call('route:clear');
    Artisan::call('route:cache');

    return 'Route cache cleared and rebuilt!';
});

require __DIR__.'/auth.php';
