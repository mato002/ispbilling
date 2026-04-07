<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Payment;
use App\Services\UserConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconnectUserAfterPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $subscription;
    protected $payment;

    public function __construct(Customer $customer, Subscription $subscription, Payment $payment)
    {
        $this->customer = $customer;
        $this->subscription = $subscription;
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (!$this->customer->router || !$this->customer->is_active) {
                Log::warning("Cannot reconnect customer {$this->customer->id}: No router or inactive customer");
                return;
            }

            $connectionService = new UserConnectionService($this->customer->router);
            
            // Reconnect user
            $result = $connectionService->reconnectUser($this->customer, $this->subscription);
            
            if ($result) {
                // Apply bandwidth limits
                $connectionService->applyBandwidthLimits($this->customer, $this->subscription);
                
                // Update customer status
                $this->customer->update([
                    'status' => 'active',
                    'connection_status' => 'connected',
                    'reconnected_at' => now(),
                ]);

                Log::info("Successfully reconnected user after payment: {$this->customer->username} (Payment: {$this->payment->payment_reference})");
            } else {
                Log::error("Failed to reconnect user after payment: {$this->customer->username} (Payment: {$this->payment->payment_reference})");
            }

        } catch (\Exception $e) {
            Log::error("Error reconnecting user {$this->customer->username} after payment: " . $e->getMessage());
        }
    }
}
