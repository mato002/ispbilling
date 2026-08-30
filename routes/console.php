<?php

use App\Jobs\CheckExpiredSubscriptions;
use App\Jobs\CheckExpiredVouchers;
use App\Jobs\EnforceBandwidthCaps;
use App\Jobs\GenerateInvoices;
use App\Jobs\PollRouterHealthJob;
use App\Jobs\ReactivatePaidUsers;
use App\Jobs\ReconcileHardwareStateJob;
use App\Jobs\RunPlatformSaasDailyBillingJob;
use App\Jobs\SendPaymentReminders;
use App\Jobs\SuspendUnpaidUsers;
use App\Models\Router;
use App\Models\RouterMetric;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily Automation Schedule
// Run this command via cron: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

Schedule::call(function () {
    // 1. Check expired subscriptions (runs first)
    // Marks expired subscriptions and suspends customers with unpaid invoices
    CheckExpiredSubscriptions::dispatch();

    // 2. Generate invoices for expired subscriptions
    // Creates invoices when subscriptions expire (if not already created)
    GenerateInvoices::dispatch();

    // 3. Suspend unpaid users
    // Suspends MikroTik users with overdue invoices
    SuspendUnpaidUsers::dispatch();

    // 4. Check expired vouchers
    // Suspends hotspot users with expired vouchers
    CheckExpiredVouchers::dispatch();

    // 5. Send payment reminders
    // Sends reminders for upcoming or overdue invoices
    SendPaymentReminders::dispatch();

    // 6. Enforce bandwidth/data caps
    // Suspends customers who exceeded plan data limit
    EnforceBandwidthCaps::dispatch();

    // 7. Reactivate paid users (catch-up for payments completed outside daily run)
    // Re-enables MikroTik users for customers who paid
    ReactivatePaidUsers::dispatch();
})->dailyAt('02:00')->name('daily-automation')->withoutOverlapping();

// Live NOC: poll every active router for telemetry every minute.
// Each router becomes its own queued PollRouterHealthJob so a slow / unreachable
// device cannot block the rest of the fleet.
Schedule::call(function () {
    Router::query()
        ->where('is_active', true)
        ->select('id')
        ->chunkById(100, function ($routers) {
            foreach ($routers as $router) {
                PollRouterHealthJob::dispatch($router->id);
            }
        });
})
    ->everyMinute()
    ->name('routers:poll-fleet')
    ->withoutOverlapping(2);

// Trim router_metrics to 14 days so the timeseries does not grow unbounded.
Schedule::call(function () {
    RouterMetric::where('recorded_at', '<', now()->subDays(14))->delete();
})
    ->dailyAt('03:00')
    ->name('routers:trim-metrics');

Schedule::job(new RunPlatformSaasDailyBillingJob())
    ->dailyAt('06:10')
    ->name('platform-saas:daily')
    ->withoutOverlapping();

Schedule::job(new ReconcileHardwareStateJob())
    ->everyThirtyMinutes()
    ->name('hardware:reconcile-db-vs-routeros')
    ->withoutOverlapping(25);

Schedule::command('usage:sync')
    ->everyFifteenMinutes()
    ->name('usage:sync-fleet')
    ->withoutOverlapping(10);

Schedule::job(new \App\Jobs\DispatchScheduledCommunicationCampaignsJob())
    ->everyMinute()
    ->name('communications:dispatch-scheduled')
    ->withoutOverlapping(2);

Schedule::job(new \App\Jobs\RefreshAllProvisioningSnapshotsJob())
    ->everyMinute()
    ->name('provisioning:refresh-snapshots')
    ->withoutOverlapping(55);

Schedule::job(new \App\Jobs\DispatchScheduledRouterBackupsJob())
    ->hourly()
    ->name('routers:scheduled-backups')
    ->withoutOverlapping(50);
