<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReactivatePaidUsers implements ShouldQueue
{
    use Queueable;

    protected $isp;
    protected $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment = null, ISP $isp = null)
    {
        $this->payment = $payment;
        $this->isp = $isp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->payment) {
            // Reactivate for a specific payment
            $this->reactivateForPayment($this->payment);
        } else {
            // Reactivate all customers with paid invoices but still suspended
            $isps = $this->isp ? collect([$this->isp]) : ISP::where('is_active', true)->get();
            
            foreach ($isps as $isp) {
                $this->reactivatePaidUsersForIsp($isp);
            }
        }
    }

    /**
     * Reactivate customer for a specific payment
     */
    protected function reactivateForPayment(Payment $payment): void
    {
        if ($payment->status !== 'completed') {
            Log::warning("Attempted to reactivate user for non-completed payment {$payment->id}");
            return;
        }

        $customer = $payment->customer;
        $invoice = $payment->invoice;

        if (!$customer) {
            Log::error("Customer not found for payment {$payment->id}");
            return;
        }

        // Check if already reactivated (idempotent check)
        if ($customer->status === 'active' && $customer->router && $customer->mikrotik_user_id) {
            // Verify MikroTik user is enabled by checking if we need to reactivate
            // For now, proceed with reactivation to ensure it's done
        }

        try {
            // 1. Reactivate MikroTik user
            if ($customer->router && $customer->mikrotik_user_id) {
                $mikrotik = new MikroTikService($customer->router);
                
                // Enable user on router
                if ($mikrotik->enableUser($customer)) {
                    Log::info("Reactivated MikroTik user for customer {$customer->id}");
                } else {
                    Log::warning("Failed to reactivate MikroTik user for customer {$customer->id}");
                }
            }

            // 2. Activate customer if suspended
            if ($customer->status !== 'active') {
                $customer->activate();
                Log::info("Activated customer {$customer->id} after payment");
            }

            // 3. Activate subscription if it's suspended, expired, or needs renewal
            if ($invoice && $invoice->subscription_id) {
                $subscription = Subscription::find($invoice->subscription_id);
                
                if ($subscription) {
                    // Activate subscription if it's suspended (new purchase) or expired
                    if ($subscription->status === 'suspended') {
                        // New subscription - activate it
                        $subscription->update([
                            'status' => 'active',
                            'start_date' => now(),
                            'end_date' => now()->addDays($subscription->plan->duration_days),
                            'renewal_date' => now()->addDays($subscription->plan->duration_days),
                        ]);
                        Log::info("Activated subscription {$subscription->id} for customer {$customer->id} after payment");
                    } elseif ($subscription->status === 'expired' || 
                        ($subscription->status === 'active' && $subscription->end_date <= now()->addDays(7))) {
                        // Renew existing subscription
                        $subscription->renew();
                        Log::info("Renewed subscription {$subscription->id} for customer {$customer->id}");
                    } elseif ($subscription->status === 'active') {
                        // Subscription is already active, just ensure dates are correct
                        if ($subscription->end_date < now()) {
                            $subscription->update([
                                'start_date' => now(),
                                'end_date' => now()->addDays($subscription->plan->duration_days),
                                'renewal_date' => now()->addDays($subscription->plan->duration_days),
                            ]);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Error reactivating customer {$customer->id} after payment {$payment->id}: " . $e->getMessage());
        }
    }

    /**
     * Reactivate all paid users for an ISP
     */
    protected function reactivatePaidUsersForIsp(ISP $isp): void
    {
        // Get customers with paid invoices but still suspended
        $customers = Customer::where('isp_id', $isp->id)
            ->where('status', 'suspended')
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'paid')
                      ->where('paid_date', '>=', now()->subDays(1)); // Paid in last 24 hours
            })
            ->with(['router', 'invoices' => function ($query) {
                $query->where('status', 'paid')
                      ->latest()
                      ->limit(1);
            }])
            ->get();

        foreach ($customers as $customer) {
            try {
                // Reactivate MikroTik user
                if ($customer->router && $customer->mikrotik_user_id) {
                    $mikrotik = new MikroTikService($customer->router);
                    $mikrotik->enableUser($customer);
                }

                // Activate customer
                $customer->activate();

                Log::info("Reactivated customer {$customer->id} for ISP {$isp->id}");

            } catch (\Exception $e) {
                Log::error("Failed to reactivate customer {$customer->id}: " . $e->getMessage());
            }
        }
    }
}

