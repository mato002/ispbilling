<?php

namespace App\Jobs;

use App\Services\PlatformSaasBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunPlatformSaasDailyBillingJob implements ShouldQueue
{
    use Queueable;

    public function handle(PlatformSaasBillingService $billing): void
    {
        $billing->runDailyCycle();
    }
}
