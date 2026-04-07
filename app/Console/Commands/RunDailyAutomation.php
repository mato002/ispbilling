<?php

namespace App\Console\Commands;

use App\Jobs\CheckExpiredSubscriptions;
use App\Jobs\CheckExpiredVouchers;
use App\Jobs\GenerateInvoices;
use App\Jobs\SendPaymentReminders;
use App\Jobs\SuspendUnpaidUsers;
use Illuminate\Console\Command;

class RunDailyAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:daily-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all daily automation tasks: check expired subscriptions, generate invoices, suspend unpaid users, and send payment reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily automation tasks...');

        // 1. Check expired subscriptions
        $this->info('1. Checking expired subscriptions...');
        CheckExpiredSubscriptions::dispatch();
        $this->info('   ✓ Expired subscriptions check queued');

        // 2. Generate invoices for expired subscriptions
        $this->info('2. Generating invoices for expired subscriptions...');
        GenerateInvoices::dispatch();
        $this->info('   ✓ Invoice generation queued');

        // 3. Suspend unpaid users
        $this->info('3. Suspending unpaid users...');
        SuspendUnpaidUsers::dispatch();
        $this->info('   ✓ Unpaid user suspension queued');

        // 4. Check expired vouchers
        $this->info('4. Checking expired vouchers...');
        CheckExpiredVouchers::dispatch();
        $this->info('   ✓ Expired vouchers check queued');

        // 5. Send payment reminders
        $this->info('5. Sending payment reminders...');
        SendPaymentReminders::dispatch();
        $this->info('   ✓ Payment reminders queued');

        $this->info('');
        $this->info('All daily automation tasks have been queued successfully!');
        $this->info('Make sure your queue worker is running: php artisan queue:work');

        return Command::SUCCESS;
    }
}
