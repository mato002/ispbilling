<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->notificationService = app(NotificationService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send payment reminders
            $this->notificationService->sendPaymentReminders();
            
            // Cleanup old notifications (older than 90 days)
            $this->notificationService->cleanupOldNotifications();
            
            Log::info('Scheduled notifications job completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Error in scheduled notifications job: ' . $e->getMessage());
            
            // Retry the job if it fails
            $this->release(300); // Release for 5 minutes and retry
        }
    }
}
