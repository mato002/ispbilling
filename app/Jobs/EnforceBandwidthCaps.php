<?php

namespace App\Jobs;

use App\Models\CustomerUsageStat;
use App\Models\Subscription;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnforceBandwidthCaps implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find active subscriptions whose plans have a data limit
        $subscriptions = Subscription::with(['plan', 'customer.router'])
            ->where('status', 'active')
            ->whereHas('plan', function ($q) {
                $q->whereNotNull('data_limit_mb')
                    ->where('data_limit_mb', '>', 0);
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->plan;
            $customer = $subscription->customer;

            if (! $customer || ! $customer->router) {
                continue;
            }

            // Sum usage for this subscription
            $totalBytes = CustomerUsageStat::where('subscription_id', $subscription->id)->sum('total_bytes');

            if ($totalBytes <= 0) {
                continue;
            }

            $limitBytes = $plan->data_limit_mb * 1024 * 1024;

            if ($totalBytes >= $limitBytes) {
                $this->suspendForCap($subscription, $totalBytes, $limitBytes);
            }
        }
    }

    protected function suspendForCap(Subscription $subscription, int $totalBytes, int $limitBytes): void
    {
        $customer = $subscription->customer;

        try {
            $router = $customer->router;
            $mikrotik = new MikroTikService($router);

            // Disconnect any active session and disable user
            $mikrotik->disconnectSession($customer);
            $mikrotik->disableUser($customer);

            // Update subscription and customer status (track when cap was enforced)
            $subscription->update([
                'status' => 'suspended_due_to_cap',
                'last_cap_enforced_at' => now(),
            ]);
            $customer->suspend();

            Log::info(sprintf(
                'Suspended customer %d for exceeding data cap on subscription %d (usage=%d bytes, limit=%d bytes)',
                $customer->id,
                $subscription->id,
                $totalBytes,
                $limitBytes
            ));
        } catch (\Throwable $e) {
            Log::error('Failed enforcing bandwidth cap for subscription '.$subscription->id.': '.$e->getMessage());
        }
    }
}

