<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledNotifications;
use Illuminate\Console\Command;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled notifications and cleanup old ones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending scheduled notifications...');
        
        // Dispatch the job to handle notifications
        SendScheduledNotifications::dispatch();
        
        $this->info('Scheduled notifications job dispatched successfully!');
        
        return Command::SUCCESS;
    }
}
