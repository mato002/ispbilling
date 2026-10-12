<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Subscription;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions implements ShouldQueue
{
    use Queueable;

    protected $isp;

    /**
     * Create a new job instance.
     */
    public function __construct(?ISP $isp = null)
    {
        $this->isp = $isp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $isps = $this->isp ? collect([$this->isp]) : ISP::where('is_active', true)->get();

        foreach ($isps as $isp) {
            $this->checkExpiredForIsp($isp);
        }
    }

    protected function checkExpiredForIsp(ISP $isp): void
    {
        // Get active subscriptions that have expired
        $expiredSubscriptions = Subscription::whereHas('customer', function ($query) use ($isp) {
            $query->where('isp_id', $isp->id);
        })
        ->where('status', 'active')
        ->where(function ($query) {
            $query->whereNotNull('ends_at')->where('ends_at', '<=', now())
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('ends_at')->whereDate('end_date', '<', today());
                });
        })
        ->with(['customer.router', 'customer.invoices' => function ($query) {
            $query->where('status', 'pending')
                  ->orWhere('status', 'overdue');
        }])
        ->get();

        foreach ($expiredSubscriptions as $subscription) {
            try {
                $customer = $subscription->customer;
                
                // Check if there are unpaid invoices
                $hasUnpaidInvoices = $customer->invoices()
                    ->whereIn('status', ['pending', 'overdue'])
                    ->exists();

                // Revoke access before changing database state. If RouterOS is
                // unreachable, keep this subscription active so the next run retries.
                if ($customer->router) {
                    $mikrotik = new MikroTikService($customer->router);
                    if (! $mikrotik->disconnectSession($customer) || ! $mikrotik->disableUser($customer)) {
                        Log::warning("Router enforcement failed for expired subscription {$subscription->id}; will retry");
                        continue;
                    }
                }

                if ($hasUnpaidInvoices) {
                    $customer->suspend();
                    $subscription->expire();
                    
                    Log::info("Expired and suspended subscription {$subscription->id} for customer {$customer->id} due to unpaid invoices");
                } else {
                    // If no unpaid invoices, just mark as expired
                    // This handles cases where subscription expired but was already paid
                    $subscription->expire();
                    
                    Log::info("Marked subscription {$subscription->id} as expired for customer {$customer->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to process expired subscription {$subscription->id}: " . $e->getMessage());
            }
        }
    }
}
