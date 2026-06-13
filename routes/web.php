<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\GlobalPromoCodeController;
use App\Http\Controllers\Admin\HelpdeskTicketController;
use App\Http\Controllers\Admin\IntegrationsController;
use App\Http\Controllers\Admin\ISPController;
use App\Http\Controllers\Admin\IspSaasOnboardingController;
use App\Http\Controllers\Admin\PlatformAuditController;
use App\Http\Controllers\Admin\PlatformFinanceController;
use App\Http\Controllers\Admin\PlatformIntelligenceController;
use App\Http\Controllers\Admin\PlatformMarketingController;
use App\Http\Controllers\Admin\PlatformNetworkController;
use App\Http\Controllers\Admin\PlatformSaasPlanController;
use App\Http\Controllers\Admin\PortalSettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SystemAnnouncementController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\PlanController;
use App\Http\Controllers\Customer\SelfServiceController;
use App\Http\Controllers\Hotspot\PortalController;
use App\Http\Controllers\ISP\Web\ActiveUsersController;
use App\Http\Controllers\ISP\Web\ActivityLogController;
use App\Http\Controllers\ISP\Web\BandwidthProfileController;
use App\Http\Controllers\ISP\Web\HelpdeskController;
use App\Http\Controllers\ISP\Web\HotspotServerController;
use App\Http\Controllers\ISP\Web\InvoiceController;
use App\Http\Controllers\ISP\Web\IpPoolController;
use App\Http\Controllers\ISP\Web\NotificationController;
use App\Http\Controllers\ISP\Web\PaymentMethodController;
use App\Http\Controllers\ISP\Web\PaymentSettingsController;
use App\Http\Controllers\ISP\Web\PlatformSubscriptionController;
use App\Http\Controllers\ISP\Web\RouterActionLogController;
use App\Http\Controllers\ISP\Web\RouterController;
use App\Http\Controllers\ISP\Web\OnboardingPaymentStatusController;
use App\Http\Controllers\ISP\Web\SaasOnboardingController;
use App\Http\Controllers\ISP\Web\SessionController;
use App\Http\Controllers\ISP\Web\SubscriptionController;
use App\Http\Controllers\ISP\Web\TicketController;
use App\Http\Controllers\ISP\Web\UserConnectionController;
use App\Http\Controllers\ISP\Web\VoucherController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaticPageController;
use App\Http\Middleware\HandlePaymentWebhook;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('welcome');

// Static Pages
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/documentation', [StaticPageController::class, 'documentation'])->name('documentation');

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

Route::middleware(['auth', 'load.tenant.isp'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ISP SaaS onboarding (no ensure.saas.onboarding — completes plan + billing before console)
    Route::middleware('ensure.isp.platform')->prefix('isp')->name('isp.')->group(function () {
        Route::get('/saas-onboarding', [SaasOnboardingController::class, 'show'])->name('saas-onboarding.show');
        Route::post('/saas-onboarding', [SaasOnboardingController::class, 'store'])->name('saas-onboarding.store');
    });

    Route::middleware('ensure.isp.platform')->group(function () {
        Route::get('/api/onboarding/payment-status/{payment}', [OnboardingPaymentStatusController::class, 'show'])
            ->name('api.onboarding.payment-status');
    });

    // ISP Dashboard Routes (for ISP Admin and Staff)
    Route::middleware(['ensure.isp.platform', 'ensure.saas.onboarding'])->prefix('isp')->name('isp.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\ISP\Web\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/platform-subscription', [PlatformSubscriptionController::class, 'index'])->name('platform-subscription.index');
        Route::post('/platform-subscription/invoices/{invoice}/pay', [PlatformSubscriptionController::class, 'payInvoice'])->name('platform-subscription.pay');
        Route::get('/platform-subscription/invoices/{invoice}', [PlatformSubscriptionController::class, 'downloadInvoice'])->name('platform-subscription.invoice');

        // Customers
        Route::get('/customers', [App\Http\Controllers\ISP\Web\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [App\Http\Controllers\ISP\Web\CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [App\Http\Controllers\ISP\Web\CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [App\Http\Controllers\ISP\Web\CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [App\Http\Controllers\ISP\Web\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [App\Http\Controllers\ISP\Web\CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [App\Http\Controllers\ISP\Web\CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{customer}/suspend', [App\Http\Controllers\ISP\Web\CustomerController::class, 'suspend'])->name('customers.suspend');
        Route::post('/customers/{customer}/activate', [App\Http\Controllers\ISP\Web\CustomerController::class, 'activate'])->name('customers.activate');

        // Plans
        Route::get('/plans', [App\Http\Controllers\ISP\Web\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [App\Http\Controllers\ISP\Web\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [App\Http\Controllers\ISP\Web\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}', [App\Http\Controllers\ISP\Web\PlanController::class, 'show'])->name('plans.show');
        Route::get('/plans/{plan}/edit', [App\Http\Controllers\ISP\Web\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [App\Http\Controllers\ISP\Web\PlanController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/activate', [App\Http\Controllers\ISP\Web\PlanController::class, 'activate'])->name('plans.activate');
        Route::post('/plans/{plan}/deactivate', [App\Http\Controllers\ISP\Web\PlanController::class, 'deactivate'])->name('plans.deactivate');
        Route::post('/plans/{plan}/toggle-active', [App\Http\Controllers\ISP\Web\PlanController::class, 'toggleActive'])->name('plans.toggle-active');

        // IP Pools
        Route::prefix('ip-pools')->name('ip-pools.')->group(function () {
            Route::get('/', [IpPoolController::class, 'index'])->name('index');
            Route::get('/create', [IpPoolController::class, 'create'])->name('create');
            Route::post('/', [IpPoolController::class, 'store'])->name('store');
            Route::post('/sync', [IpPoolController::class, 'sync'])->name('sync');
            Route::get('/available', [IpPoolController::class, 'getAvailable'])->name('available');
            Route::get('/{ipPool}', [IpPoolController::class, 'show'])->name('show');
            Route::get('/{ipPool}/edit', [IpPoolController::class, 'edit'])->name('edit');
            Route::put('/{ipPool}', [IpPoolController::class, 'update'])->name('update');
            Route::delete('/{ipPool}', [IpPoolController::class, 'destroy'])->name('destroy');
            Route::post('/{ipPool}/activate', [IpPoolController::class, 'activate'])->name('activate');
            Route::post('/{ipPool}/deactivate', [IpPoolController::class, 'deactivate'])->name('deactivate');
        });

        // Active Users Monitoring
        Route::prefix('active-users')->name('active-users.')->group(function () {
            Route::get('/', [ActiveUsersController::class, 'index'])->name('index');
            Route::get('/realtime', [ActiveUsersController::class, 'getRealTimeUsers'])->name('realtime');
            Route::post('/disconnect', [ActiveUsersController::class, 'disconnectUser'])->name('disconnect');
            Route::get('/stats', [ActiveUsersController::class, 'getUserStats'])->name('stats');
        });

        // User Connection Management
        Route::prefix('user-connections')->name('user-connections.')->group(function () {
            Route::get('/', [UserConnectionController::class, 'index'])->name('index');
            Route::post('/run-automatic', [UserConnectionController::class, 'runAutomatic'])->name('run-automatic');
            Route::get('/statistics', [UserConnectionController::class, 'getStatistics'])->name('statistics');
            Route::post('/batch', [UserConnectionController::class, 'batchOperation'])->name('batch');
            Route::post('/{customer}/disconnect', [UserConnectionController::class, 'disconnect'])->name('disconnect');
            Route::post('/{customer}/suspend', [UserConnectionController::class, 'suspend'])->name('suspend');
            Route::post('/{customer}/reconnect', [UserConnectionController::class, 'reconnect'])->name('reconnect');
            Route::get('/{customer}/status', [UserConnectionController::class, 'getStatus'])->name('status');
        });

        // Bandwidth Profiles
        Route::prefix('bandwidth-profiles')->name('bandwidth-profiles.')->group(function () {
            Route::get('/', [BandwidthProfileController::class, 'index'])->name('index');
            Route::get('/create', [BandwidthProfileController::class, 'create'])->name('create');
            Route::post('/', [BandwidthProfileController::class, 'store'])->name('store');
            Route::get('/available', [BandwidthProfileController::class, 'getAvailable'])->name('available');
            Route::get('/{bandwidthProfile}', [BandwidthProfileController::class, 'show'])->name('show');
            Route::get('/{bandwidthProfile}/edit', [BandwidthProfileController::class, 'edit'])->name('edit');
            Route::put('/{bandwidthProfile}', [BandwidthProfileController::class, 'update'])->name('update');
            Route::delete('/{bandwidthProfile}', [BandwidthProfileController::class, 'destroy'])->name('destroy');
            Route::post('/{bandwidthProfile}/activate', [BandwidthProfileController::class, 'activate'])->name('activate');
            Route::post('/{bandwidthProfile}/deactivate', [BandwidthProfileController::class, 'deactivate'])->name('deactivate');
            Route::post('/{bandwidthProfile}/deploy', [BandwidthProfileController::class, 'deploy'])->name('deploy');
        });

        // Hotspot Servers
        Route::prefix('hotspot-servers')->name('hotspot-servers.')->group(function () {
            Route::get('/', [HotspotServerController::class, 'index'])->name('index');
            Route::post('/sync', [HotspotServerController::class, 'sync'])->name('sync');
            Route::get('/available', [HotspotServerController::class, 'getAvailable'])->name('available');
            Route::get('/{hotspotServer}', [HotspotServerController::class, 'show'])->name('show');
            Route::get('/{hotspotServer}/edit', [HotspotServerController::class, 'edit'])->name('edit');
            Route::put('/{hotspotServer}', [HotspotServerController::class, 'update'])->name('update');
            Route::delete('/{hotspotServer}', [HotspotServerController::class, 'destroy'])->name('destroy');
            Route::post('/{hotspotServer}/activate', [HotspotServerController::class, 'activate'])->name('activate');
            Route::post('/{hotspotServer}/deactivate', [HotspotServerController::class, 'deactivate'])->name('deactivate');
        });

        // Routers
        Route::get('/routers', [RouterController::class, 'index'])->name('routers.index');
        Route::get('/routers/export.csv', [RouterController::class, 'exportCsv'])->name('routers.export.csv');
        Route::post('/routers/bulk', [RouterController::class, 'bulk'])->name('routers.bulk');
        Route::get('/routers/create', [RouterController::class, 'create'])->name('routers.create');
        Route::post('/routers/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');
        Route::post('/routers', [RouterController::class, 'store'])->name('routers.store');
        Route::get('/routers/{router}', [RouterController::class, 'show'])->name('routers.show');
        Route::get('/routers/{router}/edit', [RouterController::class, 'edit'])->name('routers.edit');
        Route::put('/routers/{router}', [RouterController::class, 'update'])->name('routers.update');
        Route::post('/routers/{router}/activate', [RouterController::class, 'activate'])->name('routers.activate');
        Route::post('/routers/{router}/deactivate', [RouterController::class, 'deactivate'])->name('routers.deactivate');
        Route::post('/routers/{router}/test', [RouterController::class, 'testExistingConnection'])->name('routers.test');
        Route::post('/routers/{router}/poll', [RouterController::class, 'poll'])->name('routers.poll');
        Route::post('/routers/{router}/reboot', [RouterController::class, 'reboot'])->name('routers.reboot');
        Route::post('/routers/{router}/ping', [RouterController::class, 'ping'])->name('routers.ping');
        Route::post('/routers/{router}/backup', [RouterController::class, 'backup'])->name('routers.backup');
        Route::get('/routers/{router}/backups/{backup}/download', [RouterController::class, 'downloadBackup'])->name('routers.backups.download');

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/customers/{customer}/invoices/create', [InvoiceController::class, 'createForCustomer'])->name('invoices.create-for-customer');
        Route::post('/customers/{customer}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');

        // Payments
        Route::get('/payments', [App\Http\Controllers\ISP\Web\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/initiate', [App\Http\Controllers\ISP\Web\PaymentController::class, 'create'])->name('payments.initiate');
        Route::post('/payments/initiate', [App\Http\Controllers\ISP\Web\PaymentController::class, 'initiate'])->name('payments.initiate.store');
        Route::get('/payments/transaction-logs', [App\Http\Controllers\ISP\Web\PaymentController::class, 'transactionLogs'])->name('payments.transaction-logs');
        Route::get('/payments/transaction-logs/{log}', [App\Http\Controllers\ISP\Web\PaymentController::class, 'showTransactionLog'])->name('payments.transaction-log.show');
        Route::post('/payments/{payment}/activate-internet', [App\Http\Controllers\ISP\Web\PaymentController::class, 'activateInternet'])->name('payments.activate-internet');
        Route::get('/payments/{payment}', [App\Http\Controllers\ISP\Web\PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/status', [App\Http\Controllers\ISP\Web\PaymentController::class, 'checkStatus'])->name('payments.status');
        Route::get('/customers/{customer}/payments', [App\Http\Controllers\ISP\Web\PaymentController::class, 'customerPayments'])->name('payments.customer');

        // Reports
        Route::get('/reports', [App\Http\Controllers\ISP\Web\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/customer-status', [App\Http\Controllers\ISP\Web\ReportController::class, 'customerStatusReport'])->name('reports.customer-status');
        Route::get('/reports/revenue-per-plan', [App\Http\Controllers\ISP\Web\ReportController::class, 'revenuePerPlanReport'])->name('reports.revenue-per-plan');
        Route::get('/reports/monthly-collections', [App\Http\Controllers\ISP\Web\ReportController::class, 'monthlyCollectionsReport'])->name('reports.monthly-collections');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
        Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');
        Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/notifications/stats', [NotificationController::class, 'stats'])->name('notifications.stats');

        // Vouchers
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/vouchers/bulk/create', [VoucherController::class, 'createBulk'])->name('vouchers.create-bulk');
        Route::post('/vouchers/bulk', [VoucherController::class, 'storeBulk'])->name('vouchers.store-bulk');
        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{voucher}/activate', [VoucherController::class, 'activate'])->name('vouchers.activate');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');

        // Settings
        Route::get('/settings', [App\Http\Controllers\ISP\Web\SettingController::class, 'index'])->name('settings.index');
        Route::get('/settings/company', [App\Http\Controllers\ISP\Web\SettingController::class, 'company'])->name('settings.company');
        Route::post('/settings/company', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateCompany'])->name('settings.company.update');
        Route::get('/settings/custom-fields', [App\Http\Controllers\ISP\Web\SettingController::class, 'customFields'])->name('settings.custom-fields');
        Route::post('/settings/custom-fields', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateCustomFields'])->name('settings.custom-fields.update');

        // Payment Method (MPESA Daraja) - CRUD
        Route::get('/settings/payment', [PaymentMethodController::class, 'index'])->name('settings.payment');
        Route::post('/settings/payment', [PaymentMethodController::class, 'store'])->name('settings.payment.store');
        Route::post('/settings/payment/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggleActive'])->name('settings.payment.toggle');
        Route::delete('/settings/payment/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('settings.payment.destroy');

        // Payment Settings - Multiple Payment Methods
        Route::get('/payment-settings', [PaymentSettingsController::class, 'index'])->name('payment-settings.index');
        Route::get('/payment-settings/create', [PaymentSettingsController::class, 'create'])->name('payment-settings.create');
        Route::post('/payment-settings', [PaymentSettingsController::class, 'store'])->name('payment-settings.store');
        Route::get('/payment-settings/{paymentMethod}/edit', [PaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
        Route::put('/payment-settings/{paymentMethod}', [PaymentSettingsController::class, 'update'])->name('payment-settings.update');
        Route::delete('/payment-settings/{paymentMethod}', [PaymentSettingsController::class, 'destroy'])->name('payment-settings.destroy');
        Route::post('/payment-settings/{paymentMethod}/toggle', [PaymentSettingsController::class, 'toggle'])->name('payment-settings.toggle');

        // Legacy payment gateways route (kept for backward compatibility)
        Route::get('/settings/payment-gateways', [App\Http\Controllers\ISP\Web\SettingController::class, 'paymentGateways'])->name('settings.payment-gateways');
        Route::post('/settings/payment-gateways', [App\Http\Controllers\ISP\Web\SettingController::class, 'updatePaymentGateways'])->name('settings.payment-gateways.update');

        Route::get('/settings/notifications', [App\Http\Controllers\ISP\Web\SettingController::class, 'notifications'])->name('settings.notifications');
        Route::post('/settings/notifications', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateNotifications'])->name('settings.notifications.update');
        Route::get('/settings/users', [App\Http\Controllers\ISP\Web\SettingController::class, 'users'])->name('settings.users');
        Route::get('/settings/billing', [App\Http\Controllers\ISP\Web\SettingController::class, 'billing'])->name('settings.billing');
        Route::post('/settings/billing', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateBilling'])->name('settings.billing.update');

        // Hotspot Sessions Management
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/disconnect', [SessionController::class, 'disconnect'])->name('sessions.disconnect');
        Route::get('/customers/{customer}/sessions', [SessionController::class, 'customerHistory'])->name('sessions.customer-history');

        // PrimeBilling helpdesk (ISP → platform / Super Admin)
        Route::get('/helpdesk', [HelpdeskController::class, 'index'])->name('helpdesk.index');
        Route::get('/helpdesk/create', [HelpdeskController::class, 'create'])->name('helpdesk.create');
        Route::post('/helpdesk', [HelpdeskController::class, 'store'])->name('helpdesk.store');
        Route::get('/helpdesk/{ticket}', [HelpdeskController::class, 'show'])->name('helpdesk.show');
        Route::put('/helpdesk/{ticket}', [HelpdeskController::class, 'update'])->name('helpdesk.update');
        Route::post('/helpdesk/{ticket}/reply', [HelpdeskController::class, 'reply'])->name('helpdesk.reply');

        // Support Tickets
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');

        // Activity & Audit Logs
        Route::get('/logs/activity', [ActivityLogController::class, 'index'])->name('logs.activity.index');
        Route::get('/logs/activity/export/csv', [ActivityLogController::class, 'exportCsv'])->name('logs.activity.export.csv');
        Route::get('/logs/activity/export/pdf', [ActivityLogController::class, 'exportPdf'])->name('logs.activity.export.pdf');
        Route::get('/logs/activity/{activityLog}', [ActivityLogController::class, 'show'])->name('logs.activity.show');
        Route::get('/logs/router-actions', [RouterActionLogController::class, 'index'])->name('logs.router-actions.index');
        Route::get('/logs/router-actions/{routerActionLog}', [RouterActionLogController::class, 'show'])->name('logs.router-actions.show');
    });
});

// Hotspot Portal Routes (Public - No Auth Required)
Route::prefix('hotspot')->name('hotspot.')->group(function () {
    Route::get('/packages', [PortalController::class, 'packages'])->name('packages');
    Route::get('/splash', [PortalController::class, 'splash'])->name('splash');
    Route::get('/login', [PortalController::class, 'login'])->name('login');
    Route::post('/login', [PortalController::class, 'authenticate'])->name('authenticate');
    Route::get('/status', [PortalController::class, 'status'])->name('status');
    Route::post('/logout', [PortalController::class, 'logout'])->name('logout');
    Route::get('/redeem', [PortalController::class, 'redeemVoucher'])->name('redeem');
    Route::post('/redeem', [PortalController::class, 'processVoucher'])->name('process-voucher');

    // Direct package purchase from hotspot
    Route::get('/buy/{plan}', [PortalController::class, 'buyPackage'])->name('buy-package');
    Route::post('/buy/{plan}', [PortalController::class, 'processPurchase'])->name('process-purchase');

    // Customer checkout access (after successful payment)
    Route::get('/checkout/access/{payment}', [CheckoutController::class, 'access'])
        ->name('checkout.access');
});

// Customer Self-Service Portal Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [SelfServiceController::class, 'login'])->name('login');
    Route::post('/login', [SelfServiceController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [SelfServiceController::class, 'logout'])->name('logout');

    Route::middleware('ensure.landing.isp')->group(function () {
        Route::get('/register', [SelfServiceController::class, 'register'])->name('register');
        Route::post('/register', [SelfServiceController::class, 'registerStore'])->name('register.store');
    });

    // Captive portal route (public, validates ISP via middleware)
    Route::get('/portal', [App\Http\Controllers\Customer\PortalController::class, 'portal'])
        ->middleware('validate.isp')
        ->name('portal');

    // Checkout page (public, can be accessed with customer ID)
    Route::get('/checkout', [SelfServiceController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/select-plan', [SelfServiceController::class, 'selectPlan'])->name('checkout.select-plan');

    // Public payment initiation for checkout (no auth required)
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');

    Route::middleware('customer.session')->group(function () {
        Route::get('/dashboard', [SelfServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/usage', [SelfServiceController::class, 'usage'])->name('usage');
        Route::get('/invoices', [SelfServiceController::class, 'invoices'])->name('invoices');
        Route::get('/sessions', [SelfServiceController::class, 'sessions'])->name('sessions');
        Route::post('/change-password', [SelfServiceController::class, 'changePassword'])->name('change-password');
    });
});

// Customer Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');

    // Plans
    Route::get('/plans', [PlanController::class, 'index'])->name('plans');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');

    // Hotspot checkout lives in the SelfService group above (customer.checkout).
    // Legacy PaymentController wizard routes removed — views customer.payment-checkout,
    // customer.payment-processing, and customer.payment-success do not exist.

    // Payment provider callbacks (webhook-style; no Blade views)
    Route::post('/payment/callback/{provider}', [App\Http\Controllers\Customer\PaymentController::class, 'callback'])->name('payment.callback')->middleware(HandlePaymentWebhook::class);
});

// Admin Routes — super_admin only (middleware redirects everyone else)
Route::middleware(['auth', 'ensure.superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // ISP Management
    Route::get('/isps', [ISPController::class, 'index'])->name('isps.index');
    Route::get('/isps/create', [ISPController::class, 'create'])->name('isps.create');
    Route::post('/isps', [ISPController::class, 'store'])->name('isps.store');
    Route::get('/isps/{isp}', [ISPController::class, 'show'])->name('isps.show');
    Route::get('/isps/{isp}/saas-onboarding', [IspSaasOnboardingController::class, 'show'])->name('isps.saas-onboarding.show');
    Route::post('/isps/{isp}/saas-onboarding', [IspSaasOnboardingController::class, 'store'])->name('isps.saas-onboarding.store');
    Route::get('/isps/{isp}/edit', [ISPController::class, 'edit'])->name('isps.edit');
    Route::put('/isps/{isp}', [ISPController::class, 'update'])->name('isps.update');
    Route::post('/isps/{isp}/force-suspend-network', [ISPController::class, 'forceSuspendNetwork'])->name('isps.force-suspend-network');
    Route::delete('/isps/{isp}', [ISPController::class, 'destroy'])->name('isps.destroy');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/payments', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/finance', [PlatformFinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/commission', [PlatformFinanceController::class, 'updateCommission'])->name('finance.commission');
    Route::post('/finance/payouts', [PlatformFinanceController::class, 'storePayout'])->name('finance.payouts.store');
    Route::put('/finance/payouts/{payout}', [PlatformFinanceController::class, 'updatePayoutStatus'])->name('finance.payouts.update');

    Route::resource('platform-saas-plans', PlatformSaasPlanController::class)->except(['show']);

    Route::get('/audit/activity', [PlatformAuditController::class, 'activity'])->name('audit.activity');
    Route::get('/audit/activity/{activityLog}', [PlatformAuditController::class, 'showActivity'])->name('audit.activity.show');
    Route::get('/audit/security', [PlatformAuditController::class, 'security'])->name('audit.security');

    Route::get('/network', [PlatformNetworkController::class, 'index'])->name('network.index');
    Route::post('/network/dispatch', [PlatformNetworkController::class, 'dispatchMaintenance'])->name('network.dispatch');
    Route::post('/network/routers/{router}/reset-circuit', [PlatformNetworkController::class, 'resetRouterCircuit'])->name('network.routers.reset-circuit');

    Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');

    Route::get('/intelligence', [PlatformIntelligenceController::class, 'index'])->name('intelligence.index');

    Route::get('/marketing', [PlatformMarketingController::class, 'index'])->name('marketing.index');
    Route::post('/marketing', [PlatformMarketingController::class, 'update'])->name('marketing.update');

    Route::get('/promo-codes', [GlobalPromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::get('/promo-codes/create', [GlobalPromoCodeController::class, 'create'])->name('promo-codes.create');
    Route::post('/promo-codes', [GlobalPromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::get('/promo-codes/{globalPromoCode}/edit', [GlobalPromoCodeController::class, 'edit'])->name('promo-codes.edit');
    Route::put('/promo-codes/{globalPromoCode}', [GlobalPromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::delete('/promo-codes/{globalPromoCode}', [GlobalPromoCodeController::class, 'destroy'])->name('promo-codes.destroy');

    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
    Route::put('/integrations', [IntegrationsController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/vault/clear', [IntegrationsController::class, 'clearVaultKey'])->name('integrations.vault.clear');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/optimize', [SettingController::class, 'optimize'])->name('settings.optimize');

    // ISP → platform helpdesk queue
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/', [HelpdeskTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [HelpdeskTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [HelpdeskTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/reply', [HelpdeskTicketController::class, 'reply'])->name('reply');
    });

    Route::resource('announcements', SystemAnnouncementController::class)->except(['show']);

    // Hotspot Portal Settings
    Route::get('/portal-settings', [PortalSettingController::class, 'index'])->name('portal-settings.index');
    Route::put('/portal-settings', [PortalSettingController::class, 'update'])->name('portal-settings.update');
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
