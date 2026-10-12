<?php

namespace App\Services;

use App\Jobs\RetryRouterProvisioning;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\PaymentReceived;
use App\Support\IspPaymentExperience;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent side effects after a tenant payment is marked completed (router, subscription, customer notify).
 */
class PaymentCompletionService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}
    /**
     * Complete a payment row and run tenant side effects (router, subscription, notify).
     */
    public function markAsCompleted(Payment $payment, bool $forceServiceAutomation = false): void
    {
        DB::transaction(function () use ($payment) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if ($locked->status !== 'completed') $locked->markAsCompleted();
        });

        $this->processTenantPaymentSuccess($payment->fresh(), $forceServiceAutomation);
    }

    public function processTenantPaymentSuccess(Payment $payment, bool $forceServiceAutomation = false): void
    {
        $lock = Cache::lock('payment-completion:'.$payment->getKey(), 300);
        $lock->block(15, function () use ($payment, $forceServiceAutomation) {
            $this->processTenantPaymentSuccessOnce($payment, $forceServiceAutomation);
        });
    }

    private function processTenantPaymentSuccessOnce(Payment $payment, bool $forceServiceAutomation = false): void
    {
        if ($payment->fresh()?->status !== 'completed') return;
        if ($payment->isPlatformSaasPayment()) {
            return;
        }

        $payment->refresh();

        if (! empty($payment->metadata['payment_success_side_effects_at'])) {
            return;
        }

        $customer = $payment->customer;
        $invoice = $payment->invoice;

        if (! $customer) {
            return;
        }

        // A prepaid captive-portal purchase must activate the purchased service
        // to complete checkout, regardless of the ISP's postpaid auto-activation
        // preference for ordinary invoices.
        $runAutomation = $forceServiceAutomation
            || $customer->connection_type === 'hotspot'
            || IspPaymentExperience::autoActivateOnPayment($payment->isp);
        $subscriptionRenewed = false;

        try {
            if ($runAutomation) {
                if ($invoice && $invoice->subscription_id) {
                    $subscription = Subscription::query()->find($invoice->subscription_id);

                    if ($subscription && in_array($subscription->status, ['active', 'expired', 'suspended'], true)) {
                        $subscription->loadMissing('plan');
                        $subscription->renew();
                        $subscriptionRenewed = true;
                        Log::info("Renewed subscription {$subscription->id} for customer {$customer->id} after payment {$payment->id}");
                    }
                }

                if ($customer->status !== 'active') {
                    $customer->activate();
                }

                if ($customer->router && ($customer->mikrotik_user_id || $customer->username)) {
                    $mikrotik = new MikroTikService($customer->router);
                    $countersReady = ! ($customer->connection_type === 'hotspot'
                        && $subscriptionRenewed
                        && $customer->mikrotik_user_id)
                        || $mikrotik->resetHotspotUserCounters($customer);
                    $routerReady = $countersReady && ($customer->connection_type === 'hotspot'
                        ? $mikrotik->activateHotspotUser($customer)
                        : $mikrotik->enableUser($customer));

                    if (! $routerReady) {
                        Log::warning("Payment successful but router provisioning failed for customer {$customer->id} (payment {$payment->id}). Scheduling retries.", [
                            'router_id' => $customer->router_id,
                            'connection_type' => $customer->connection_type,
                        ]);

                        $chainKey = 'router-provision-retry-chain:'.$customer->id.':'.$payment->id;
                        if (Cache::add($chainKey, 1, now()->addMinutes(70))) {
                            RetryRouterProvisioning::dispatch($customer->id, $payment->id, 1)
                                ->delay(now()->addMinutes(1));
                        }
                    }
                }
            }

            if ($customer->email || $customer->phone_number) {
                try {
                    $customer->notify(new PaymentReceived($payment));
                    Log::info("Payment notification sent to customer {$customer->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send payment notification to customer {$customer->id}: ".$e->getMessage());
                }
            }

            try {
                $this->notificationService->paymentReceived($payment);
            } catch (\Throwable $e) {
                Log::error("Failed to create ISP in-app payment notification for payment {$payment->id}: ".$e->getMessage());
            }

            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'payment_success_side_effects_at' => now()->toIso8601String(),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing payment success for payment {$payment->id}: ".$e->getMessage());
        }
    }
}
