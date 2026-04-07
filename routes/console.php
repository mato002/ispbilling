<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckExpiredSubscriptions;
use App\Jobs\CheckExpiredVouchers;
use App\Jobs\EnforceBandwidthCaps;
use App\Jobs\GenerateInvoices;
use App\Jobs\ReactivatePaidUsers;
use App\Jobs\SendPaymentReminders;
use App\Jobs\SuspendUnpaidUsers;

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
