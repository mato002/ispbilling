<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\GlobalPromoCodeController;
use App\Http\Controllers\Admin\HelpdeskTicketController;
use App\Http\Controllers\Admin\IntegrationsController;
use App\Http\Controllers\Admin\ISPController;
use App\Http\Controllers\Admin\IspSaasOnboardingController;
use App\Http\Controllers\Admin\PlatformAuditController;
use App\Http\Controllers\Admin\PlatformCommunicationsController;
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
use App\Http\Controllers\Customer\PlanController;
use App\Http\Controllers\Customer\SelfServiceController;
use App\Http\Controllers\Customer\TicketController as CustomerTicketController;
use App\Http\Controllers\Hotspot\PortalController;
use App\Http\Controllers\ISP\Web\ActiveUsersController;
use App\Http\Controllers\ISP\Web\ActivityLogController;
use App\Http\Controllers\ISP\Web\BandwidthProfileController;
use App\Http\Controllers\ISP\Web\HelpdeskController;
use App\Http\Controllers\ISP\Web\HotspotServerController;
use App\Http\Controllers\ISP\Web\InvoiceController;
use App\Http\Controllers\ISP\Web\IpPoolController;
use App\Http\Controllers\ISP\Web\NotificationController;
use App\Http\Controllers\ISP\Web\AssignmentController;
use App\Http\Controllers\ISP\Web\CommunicationCampaignController;
use App\Http\Controllers\ISP\Web\DataUsageController;
use App\Http\Controllers\ISP\Web\InventoryController;
use App\Http\Controllers\ISP\Web\OutageController;
use App\Http\Controllers\ISP\Web\PaymentMethodController;
use App\Http\Controllers\ISP\Web\PaymentSettingsController;
use App\Http\Controllers\ISP\Web\PlatformSubscriptionController;
use App\Http\Controllers\ISP\Web\ProvisioningHubController;
use App\Http\Controllers\ISP\Web\RouterMigrationController;
use App\Http\Controllers\ISP\Web\RouterActionLogController;
use App\Http\Controllers\ISP\Web\RouterController;
use App\Http\Controllers\ISP\Web\OnboardingPaymentStatusController;
use App\Http\Controllers\ISP\Web\SaasOnboardingController;
use App\Http\Controllers\ISP\Web\SessionController;
use App\Http\Controllers\ISP\Web\SmsGroupController;
use App\Http\Controllers\ISP\Web\TechnicianInventoryController;
use App\Http\Controllers\ISP\Web\SubscriptionController;
use App\Http\Controllers\ISP\Web\TicketController;
use App\Http\Controllers\ISP\Web\UserConnectionController;
use App\Http\Controllers\ISP\Web\VoucherController;
use App\Http\Controllers\DeployMigrateController;
use App\Http\Controllers\DeploySyncController;
use App\Http\Controllers\FeatureDiscoveryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StaticPageController;
use App\Http\Middleware\HandlePaymentWebhook;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('welcome');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/deploy/sync-public', [DeploySyncController::class, 'syncPublic'])->name('deploy.sync-public');
Route::get('/deploy/migrate', [DeployMigrateController::class, 'migrate'])->name('deploy.migrate');

// Static Pages
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/documentation', [StaticPageController::class, 'documentation'])->name('documentation');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isSuperAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isTechnician()) {
        return redirect()->route('isp.technician.inventory.index');
    }

    // ISP admin/staff will use the enhanced dashboard
    if ($user->isp_id) {
        return redirect()->route('isp.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'load.tenant.isp'])->group(function () {
    Route::get('/feature-discovery/search', [FeatureDiscoveryController::class, 'search'])
        ->name('feature-discovery.search');

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
    Route::middleware(['ensure.isp.platform', 'ensure.saas.onboarding', 'ensure.isp.operator', 'ensure.isp.workspace'])->prefix('isp')->name('isp.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\ISP\Web\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/platform-subscription', [PlatformSubscriptionController::class, 'index'])->name('platform-subscription.index');
        Route::post('/platform-subscription/change-plan', [PlatformSubscriptionController::class, 'changePlan'])->name('platform-subscription.change-plan');
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
        Route::get('/plans/export.csv', [App\Http\Controllers\ISP\Web\PlanController::class, 'exportCsv'])->name('plans.export.csv');
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
            Route::get('/export.csv', [IpPoolController::class, 'exportCsv'])->name('export.csv');
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

        // Provisioning control hub (cached snapshot — no live joins in Blade)
        Route::prefix('network/provisioning-hub')->name('provisioning-hub.')->group(function () {
            Route::get('/', [ProvisioningHubController::class, 'index'])->name('index');
            Route::post('/refresh', [ProvisioningHubController::class, 'refresh'])->name('refresh');
            Route::post('/force-bind-mac', [ProvisioningHubController::class, 'forceBindMac'])->name('force-bind-mac');
            Route::post('/flush-queues', [ProvisioningHubController::class, 'flushQueues'])->name('flush-queues');
        });

        Route::prefix('network/router-migration')->name('router-migration.')->group(function () {
            Route::get('/', [RouterMigrationController::class, 'index'])->name('index');
            Route::get('/create', [RouterMigrationController::class, 'create'])->name('create');
            Route::get('/preview', [RouterMigrationController::class, 'preview'])->name('preview');
            Route::post('/', [RouterMigrationController::class, 'store'])->name('store');
            Route::get('/{batch}/status', [RouterMigrationController::class, 'status'])->name('status');
            Route::post('/{batch}/rollback', [RouterMigrationController::class, 'rollback'])->name('rollback');
            Route::get('/{batch}', [RouterMigrationController::class, 'show'])->name('show');
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
            Route::get('/export.csv', [BandwidthProfileController::class, 'exportCsv'])->name('export.csv');
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
            Route::get('/export.csv', [HotspotServerController::class, 'exportCsv'])->name('export.csv');
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

        // Communications & broadcast control center
        Route::prefix('communications')->name('communications.')->group(function () {
            Route::get('/', [CommunicationCampaignController::class, 'index'])->name('index');
            Route::get('/create', [CommunicationCampaignController::class, 'create'])->name('create');
            Route::post('/', [CommunicationCampaignController::class, 'store'])->name('store');
            Route::post('/preview-audience', [CommunicationCampaignController::class, 'previewAudience'])->name('preview-audience');
            Route::get('/groups', [SmsGroupController::class, 'index'])->name('groups.index');
            Route::post('/groups', [SmsGroupController::class, 'store'])->name('groups.store');
            Route::delete('/groups/{group}', [SmsGroupController::class, 'destroy'])->name('groups.destroy');
            Route::get('/{campaign}', [CommunicationCampaignController::class, 'show'])->name('show');
            Route::post('/{campaign}/launch', [CommunicationCampaignController::class, 'launch'])->name('launch');
        });

        // Data usage monitoring (read-only reporting)
        Route::prefix('data-usage')->name('data-usage.')->group(function () {
            Route::get('/', [DataUsageController::class, 'index'])->name('index');
            Route::get('/export.csv', [DataUsageController::class, 'exportCsv'])->name('export.csv');
            Route::get('/customers/{customer}', [DataUsageController::class, 'customer'])->name('customer');
        });

        // Outage compensation (manual admin control)
        Route::prefix('outages')->name('outages.')->group(function () {
            Route::get('/', [OutageController::class, 'index'])->name('index');
            Route::get('/create', [OutageController::class, 'create'])->name('create');
            Route::post('/', [OutageController::class, 'store'])->name('store');
            Route::get('/{outage}', [OutageController::class, 'show'])->name('show');
            Route::post('/{outage}/resolve', [OutageController::class, 'resolve'])->name('resolve');
            Route::post('/{outage}/apply-compensation', [OutageController::class, 'applyCompensation'])->name('apply-compensation');
        });

        // Inventory management
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/create', [InventoryController::class, 'create'])->name('create');
            Route::post('/', [InventoryController::class, 'store'])->name('store');
            Route::get('/faulty', [InventoryController::class, 'faulty'])->name('faulty');
            Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
            Route::get('/thresholds', [InventoryController::class, 'thresholds'])->name('thresholds');
            Route::post('/thresholds', [InventoryController::class, 'updateThresholds'])->name('thresholds.update');
            Route::get('/purchases', [InventoryController::class, 'purchases'])->name('purchases');
            Route::get('/purchases/create', [InventoryController::class, 'createPurchase'])->name('purchases.create');
            Route::post('/purchases', [InventoryController::class, 'storePurchase'])->name('purchases.store');
            Route::get('/scan', [InventoryController::class, 'scan'])->name('scan');
            Route::post('/scan/resolve', [InventoryController::class, 'resolveScan'])->name('scan.resolve');
            Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::get('/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
            Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
            Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
            Route::post('/assignments/{assignment}/complete-installation', [AssignmentController::class, 'completeInstallation'])->name('assignments.complete-installation');
            Route::post('/assignments/{assignment}/return', [AssignmentController::class, 'returnDevice'])->name('assignments.return');
            Route::post('/assignments/{assignment}/replace', [AssignmentController::class, 'replace'])->name('assignments.replace');
            Route::get('/technician-assignments', [InventoryController::class, 'technicianAssignments'])->name('technician-assignments.index');
            Route::post('/{inventoryItem}/assign-technician', [InventoryController::class, 'assignTechnician'])->name('assign-technician');
            Route::get('/{inventoryItem}', [InventoryController::class, 'show'])->name('show');
            Route::get('/{inventoryItem}/edit', [InventoryController::class, 'edit'])->name('edit');
            Route::put('/{inventoryItem}', [InventoryController::class, 'update'])->name('update');
            Route::post('/{inventoryItem}/mark-faulty', [InventoryController::class, 'markFaulty'])->name('mark-faulty');
            Route::post('/{inventoryItem}/return', [InventoryController::class, 'returnToWarehouse'])->name('return');
            Route::post('/{inventoryItem}/retire', [InventoryController::class, 'retire'])->name('retire');
        });

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/export.csv', [SubscriptionController::class, 'exportCsv'])->name('subscriptions.export.csv');
        Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/export.csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export.csv');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::get('/customers/{customer}/invoices/create', [InvoiceController::class, 'createForCustomer'])->name('invoices.create-for-customer');
        Route::post('/customers/{customer}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');

        // Payments
        Route::get('/payments', [App\Http\Controllers\ISP\Web\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/unposted', [App\Http\Controllers\ISP\Web\PaymentController::class, 'unposted'])->name('payments.unposted');
        Route::get('/payments/credits', [App\Http\Controllers\ISP\Web\PaymentController::class, 'credits'])->name('payments.credits');
        Route::get('/payments/failed', [App\Http\Controllers\ISP\Web\PaymentController::class, 'failed'])->name('payments.failed');
        Route::get('/payments/export.csv', [App\Http\Controllers\ISP\Web\PaymentController::class, 'exportCsv'])->name('payments.export.csv');
        Route::get('/payments/initiate', [App\Http\Controllers\ISP\Web\PaymentController::class, 'create'])->name('payments.initiate');
        Route::post('/payments/initiate', [App\Http\Controllers\ISP\Web\PaymentController::class, 'initiate'])->name('payments.initiate.store');
        Route::get('/payments/transaction-logs', [App\Http\Controllers\ISP\Web\PaymentController::class, 'transactionLogs'])->name('payments.transaction-logs');
        Route::get('/payments/transaction-logs/{log}', [App\Http\Controllers\ISP\Web\PaymentController::class, 'showTransactionLog'])->name('payments.transaction-log.show');
        Route::post('/payments/{payment}/assign', [App\Http\Controllers\ISP\Web\PaymentController::class, 'assignUnposted'])->name('payments.assign');
        Route::post('/payments/{payment}/force-sync', [App\Http\Controllers\ISP\Web\PaymentController::class, 'forceSync'])->name('payments.force-sync');
        Route::post('/payments/{payment}/activate-internet', [App\Http\Controllers\ISP\Web\PaymentController::class, 'activateInternet'])->name('payments.activate-internet');
        Route::post('/payments/{payment}/confirm-manual', [App\Http\Controllers\ISP\Web\PaymentController::class, 'confirmManual'])->name('payments.confirm-manual');
        Route::get('/payments/{payment}', [App\Http\Controllers\ISP\Web\PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/status', [App\Http\Controllers\ISP\Web\PaymentController::class, 'checkStatus'])->name('payments.status');
        Route::get('/customers/{customer}/payments', [App\Http\Controllers\ISP\Web\PaymentController::class, 'customerPayments'])->name('payments.customer');

        // Reports
        Route::get('/reports', [App\Http\Controllers\ISP\Web\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/customer-status', [App\Http\Controllers\ISP\Web\ReportController::class, 'customerStatusReport'])->name('reports.customer-status');
        Route::get('/reports/revenue-per-plan', [App\Http\Controllers\ISP\Web\ReportController::class, 'revenuePerPlanReport'])->name('reports.revenue-per-plan');
        Route::get('/reports/monthly-collections', [App\Http\Controllers\ISP\Web\ReportController::class, 'monthlyCollectionsReport'])->name('reports.monthly-collections');
        Route::get('/reports/export/revenue.csv', [App\Http\Controllers\ISP\Web\ReportController::class, 'exportRevenueCsv'])->name('reports.export.revenue');
        Route::get('/reports/export/collections.csv', [App\Http\Controllers\ISP\Web\ReportController::class, 'exportCollectionsCsv'])->name('reports.export.collections');
        Route::get('/reports/export/growth.csv', [App\Http\Controllers\ISP\Web\ReportController::class, 'exportGrowthCsv'])->name('reports.export.growth');

        // Notifications
        Route::middleware('ensure.isp.can:notifications.view')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notifications/export.csv', [NotificationController::class, 'exportCsv'])->name('notifications.export.csv');
            Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
            Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');
            Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
            Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('/notifications/stats', [NotificationController::class, 'stats'])->name('notifications.stats');
        });

        // Vouchers
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/export.csv', [VoucherController::class, 'exportCsv'])->name('vouchers.export.csv');
        Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/vouchers/bulk/create', [VoucherController::class, 'createBulk'])->name('vouchers.create-bulk');
        Route::post('/vouchers/bulk', [VoucherController::class, 'storeBulk'])->name('vouchers.store-bulk');
        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{voucher}/activate', [VoucherController::class, 'activate'])->name('vouchers.activate');
        Route::match(['post', 'patch'], '/vouchers/{voucher}/deactivate', [VoucherController::class, 'deactivate'])->name('vouchers.deactivate');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');

        // Settings
        Route::middleware('ensure.isp.can:settings.forms')->group(function () {
            Route::get('/settings/forms', [App\Http\Controllers\FormSettingsController::class, 'index'])->name('settings.forms.index');
            Route::post('/settings/forms', [App\Http\Controllers\FormSettingsController::class, 'update'])->name('settings.forms.update');
        });

        Route::get('/settings', [App\Http\Controllers\ISP\Web\SettingController::class, 'index'])->name('settings.index');
        Route::middleware('ensure.isp.can:settings.company')->group(function () {
            Route::get('/settings/company', [App\Http\Controllers\ISP\Web\SettingController::class, 'company'])->name('settings.company');
            Route::post('/settings/company', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateCompany'])->name('settings.company.update');
            Route::get('/settings/custom-fields', [App\Http\Controllers\ISP\Web\SettingController::class, 'customFields'])->name('settings.custom-fields');
            Route::post('/settings/custom-fields', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateCustomFields'])->name('settings.custom-fields.update');
        });

        // Payment Method (MPESA Daraja) - CRUD
        Route::get('/settings/payment', [PaymentMethodController::class, 'index'])->name('settings.payment');
        Route::post('/settings/payment', [PaymentMethodController::class, 'store'])->name('settings.payment.store');
        Route::post('/settings/payment/register-c2b', [PaymentMethodController::class, 'registerC2BUrls'])->name('settings.payment.register-c2b');
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
        Route::post('/settings/sms-wallet/topup', [App\Http\Controllers\ISP\Web\SettingController::class, 'topUpSmsWallet'])->name('settings.sms-wallet.topup');
        Route::get('/settings/users', [App\Http\Controllers\ISP\Web\SettingController::class, 'users'])->name('settings.users');
        Route::get('/settings/users/create', [App\Http\Controllers\ISP\Web\IspWorkspaceUserController::class, 'create'])->name('settings.users.create');
        Route::post('/settings/users', [App\Http\Controllers\ISP\Web\IspWorkspaceUserController::class, 'store'])->name('settings.users.store');
        Route::get('/settings/users/{user}/edit', [App\Http\Controllers\ISP\Web\IspWorkspaceUserController::class, 'edit'])->name('settings.users.edit');
        Route::put('/settings/users/{user}', [App\Http\Controllers\ISP\Web\IspWorkspaceUserController::class, 'update'])->name('settings.users.update');
        Route::get('/settings/roles', [App\Http\Controllers\ISP\Web\SettingController::class, 'roles'])->name('settings.roles');
        Route::post('/settings/roles', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateRoles'])->name('settings.roles.update');
        Route::get('/settings/billing', [App\Http\Controllers\ISP\Web\SettingController::class, 'billing'])->name('settings.billing');
        Route::post('/settings/billing', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateBilling'])->name('settings.billing.update');

        Route::get('/settings/security', [App\Http\Controllers\ISP\Web\SettingController::class, 'security'])->name('settings.security')->middleware('ensure.isp.can:settings.security');
        Route::post('/settings/security', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateSecurity'])->name('settings.security.update')->middleware('ensure.isp.can:settings.security');
        Route::get('/settings/branches', [App\Http\Controllers\ISP\Web\SettingController::class, 'branches'])->name('settings.branches');
        Route::post('/settings/branches', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateBranches'])->name('settings.branches.update');
        Route::get('/settings/router-defaults', [App\Http\Controllers\ISP\Web\SettingController::class, 'routerDefaults'])->name('settings.router-defaults');
        Route::post('/settings/router-defaults', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateRouterDefaults'])->name('settings.router-defaults.update');
        Route::get('/settings/pppoe-defaults', [App\Http\Controllers\ISP\Web\SettingController::class, 'pppoeDefaults'])->name('settings.pppoe-defaults');
        Route::post('/settings/pppoe-defaults', [App\Http\Controllers\ISP\Web\SettingController::class, 'updatePppoeDefaults'])->name('settings.pppoe-defaults.update');
        Route::get('/settings/hotspot', [App\Http\Controllers\ISP\Web\SettingController::class, 'hotspotSettings'])->name('settings.hotspot');
        Route::post('/settings/hotspot', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateHotspotSettings'])->name('settings.hotspot.update');
        Route::get('/settings/backups', [App\Http\Controllers\ISP\Web\SettingController::class, 'backups'])->name('settings.backups');
        Route::post('/settings/backups', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateBackups'])->name('settings.backups.update');
        Route::get('/settings/backups/export', [App\Http\Controllers\ISP\Web\SettingController::class, 'exportBackup'])->name('settings.backups.export');
        Route::get('/settings/maintenance', [App\Http\Controllers\ISP\Web\SettingController::class, 'maintenance'])->name('settings.maintenance');
        Route::post('/settings/maintenance', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateMaintenance'])->name('settings.maintenance.update');
        Route::get('/settings/api-integrations', [App\Http\Controllers\ISP\Web\SettingController::class, 'apiIntegrations'])->name('settings.api-integrations');
        Route::post('/settings/api-integrations', [App\Http\Controllers\ISP\Web\SettingController::class, 'updateApiIntegrations'])->name('settings.api-integrations.update');

        // Hotspot Sessions Management
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/export.csv', [SessionController::class, 'exportCsv'])->name('sessions.export.csv');
        Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/disconnect', [SessionController::class, 'disconnect'])->name('sessions.disconnect');
        Route::get('/customers/{customer}/sessions', [SessionController::class, 'customerHistory'])->name('sessions.customer-history');

        // PrimeBilling helpdesk (ISP → platform / Super Admin)
        Route::get('/helpdesk', [HelpdeskController::class, 'index'])->name('helpdesk.index');
        Route::get('/helpdesk/export.csv', [HelpdeskController::class, 'exportCsv'])->name('helpdesk.export.csv');
        Route::get('/helpdesk/create', [HelpdeskController::class, 'create'])->name('helpdesk.create');
        Route::post('/helpdesk', [HelpdeskController::class, 'store'])->name('helpdesk.store');
        Route::get('/helpdesk/{ticket}', [HelpdeskController::class, 'show'])->name('helpdesk.show');
        Route::put('/helpdesk/{ticket}', [HelpdeskController::class, 'update'])->name('helpdesk.update');
        Route::post('/helpdesk/{ticket}/reply', [HelpdeskController::class, 'reply'])->name('helpdesk.reply');

        // Support Tickets
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/export.csv', [TicketController::class, 'exportCsv'])->name('tickets.export.csv');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');

        // Activity & Audit Logs
        Route::middleware('ensure.isp.can:logs.view')->group(function () {
            Route::get('/logs/activity', [ActivityLogController::class, 'index'])->name('logs.activity.index');
            Route::get('/logs/activity/export/csv', [ActivityLogController::class, 'exportCsv'])->name('logs.activity.export.csv');
            Route::get('/logs/activity/export/pdf', [ActivityLogController::class, 'exportPdf'])->name('logs.activity.export.pdf');
            Route::get('/logs/activity/{activityLog}', [ActivityLogController::class, 'show'])->name('logs.activity.show');
            Route::get('/logs/router-actions', [RouterActionLogController::class, 'index'])->name('logs.router-actions.index');
            Route::get('/logs/router-actions/{routerActionLog}', [RouterActionLogController::class, 'show'])->name('logs.router-actions.show');
        });
    });

    // Field technician workflow (mobile-friendly inventory)
    Route::middleware(['ensure.isp.platform', 'ensure.isp.technician'])->prefix('isp/technician')->name('isp.technician.')->group(function () {
        Route::get('/inventory', [TechnicianInventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/scan', [InventoryController::class, 'scan'])->name('inventory.scan');
        Route::post('/inventory/scan/resolve', [InventoryController::class, 'resolveScan'])->name('inventory.scan.resolve');
        Route::get('/inventory/{technicianAssignment}', [TechnicianInventoryController::class, 'show'])->name('inventory.show');
        Route::post('/inventory/{technicianAssignment}/install', [TechnicianInventoryController::class, 'install'])->name('inventory.install');
        Route::post('/inventory/{technicianAssignment}/faulty', [TechnicianInventoryController::class, 'markFaulty'])->name('inventory.faulty');
        Route::post('/inventory/{technicianAssignment}/return', [TechnicianInventoryController::class, 'returnToWarehouse'])->name('inventory.return');
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

        Route::get('/tickets', [CustomerTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [CustomerTicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [CustomerTicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [CustomerTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('tickets.reply');
    });
});

// Customer Routes
Route::prefix('customer')->name('customer.')->group(function () {
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
    Route::get('/isps/export/csv', [ISPController::class, 'exportCsv'])->name('isps.export.csv');
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
    Route::get('/users/export/csv', [UserController::class, 'exportCsv'])->name('users.export.csv');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/customers/export/csv', [CustomerController::class, 'exportCsv'])->name('customers.export.csv');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/payments/export/csv', [App\Http\Controllers\Admin\PaymentController::class, 'exportCsv'])->name('payments.export.csv');
    Route::get('/payments', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/finance', [PlatformFinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/commission', [PlatformFinanceController::class, 'updateCommission'])->name('finance.commission');
    Route::post('/finance/saas-policy', [PlatformFinanceController::class, 'updateSaasPolicy'])->name('finance.saas-policy');
    Route::post('/finance/payouts', [PlatformFinanceController::class, 'storePayout'])->name('finance.payouts.store');
    Route::put('/finance/payouts/{payout}', [PlatformFinanceController::class, 'updatePayoutStatus'])->name('finance.payouts.update');

    Route::get('/platform-saas-plans/export/csv', [PlatformSaasPlanController::class, 'exportCsv'])->name('platform-saas-plans.export.csv');
    Route::resource('platform-saas-plans', PlatformSaasPlanController::class);

    Route::get('/audit/activity', [PlatformAuditController::class, 'activity'])->name('audit.activity');
    Route::get('/audit/activity/{activityLog}', [PlatformAuditController::class, 'showActivity'])->name('audit.activity.show');
    Route::get('/audit/security', [PlatformAuditController::class, 'security'])->name('audit.security');

    Route::get('/network', [PlatformNetworkController::class, 'index'])->name('network.index');
    Route::post('/network/dispatch', [PlatformNetworkController::class, 'dispatchMaintenance'])->name('network.dispatch');
    Route::post('/network/routers/{router}/reset-circuit', [PlatformNetworkController::class, 'resetRouterCircuit'])->name('network.routers.reset-circuit');

    Route::get('/system-health/export/csv', [SystemHealthController::class, 'exportCsv'])->name('system-health.export.csv');
    Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');

    Route::get('/intelligence', [PlatformIntelligenceController::class, 'index'])->name('intelligence.index');

    Route::get('/marketing', [PlatformMarketingController::class, 'index'])->name('marketing.index');
    Route::post('/marketing', [PlatformMarketingController::class, 'update'])->name('marketing.update');

    Route::get('/communications', [PlatformCommunicationsController::class, 'index'])->name('communications.index');
    Route::post('/communications/tenants/{isp}/sms-toggle', [PlatformCommunicationsController::class, 'toggleTenantSms'])->name('communications.tenants.toggle');

    Route::get('/promo-codes/export/csv', [GlobalPromoCodeController::class, 'exportCsv'])->name('promo-codes.export.csv');
    Route::get('/promo-codes', [GlobalPromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::get('/promo-codes/create', [GlobalPromoCodeController::class, 'create'])->name('promo-codes.create');
    Route::post('/promo-codes', [GlobalPromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::get('/promo-codes/{globalPromoCode}/edit', [GlobalPromoCodeController::class, 'edit'])->name('promo-codes.edit');
    Route::put('/promo-codes/{globalPromoCode}', [GlobalPromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::delete('/promo-codes/{globalPromoCode}', [GlobalPromoCodeController::class, 'destroy'])->name('promo-codes.destroy');

    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
    Route::put('/integrations', [IntegrationsController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/vault/clear', [IntegrationsController::class, 'clearVaultKey'])->name('integrations.vault.clear');

    Route::get('/settings/forms', [App\Http\Controllers\FormSettingsController::class, 'index'])->name('settings.forms.index');
    Route::post('/settings/forms', [App\Http\Controllers\FormSettingsController::class, 'update'])->name('settings.forms.update');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/optimize', [SettingController::class, 'optimize'])->name('settings.optimize');

    // ISP → platform helpdesk queue
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/export/csv', [HelpdeskTicketController::class, 'exportCsv'])->name('export.csv');
        Route::get('/', [HelpdeskTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [HelpdeskTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [HelpdeskTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/reply', [HelpdeskTicketController::class, 'reply'])->name('reply');
    });

    Route::get('/announcements/export/csv', [SystemAnnouncementController::class, 'exportCsv'])->name('announcements.export.csv');
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
