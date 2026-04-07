<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run automatic user management every 5 minutes
        $schedule->job(\App\Jobs\ManageUserConnections::class)
            ->everyFiveMinutes()
            ->description('Automatic user connection management')
            ->withoutOverlapping();

        // Run expired subscription check every hour
        $schedule->job(\App\Jobs\CheckExpiredSubscriptions::class)
            ->hourly()
            ->description('Check for expired subscriptions')
            ->withoutOverlapping();

        // Run unpaid invoice check every 6 hours
        $schedule->job(\App\Jobs\CheckUnpaidInvoices::class)
            ->everySixHours()
            ->description('Check for unpaid invoices')
            ->withoutOverlapping();

        // Clean up old payment logs daily
        $schedule->command('model:prune', [
            '--model' => \App\Models\PaymentTransactionLog::class,
        ])->daily();

        // Backup database daily at 2 AM
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->description('Daily database backup');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
