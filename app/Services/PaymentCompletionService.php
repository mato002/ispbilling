<?php

namespace App\Services;

use App\Jobs\RetryRouterProvisioning;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\PaymentReceived;
use App\Support\IspPaymentExperience;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent side effects after a tenant payment is marked completed (router, subscription, customer notify).
 */
class PaymentCompletionService
{
    public function processTenantPaymentSuccess(Payment $payment, bool $forceServiceAutomation = false): void
    {
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

        $runAutomation = $forceServiceAutomation || IspPaymentExperience::autoActivateOnPayment($payment->isp);

        try {
            if ($runAutomation) {
                if ($customer->router && ($customer->mikrotik_user_id || $customer->username)) {
                    $mikrotik = new MikroTikService($customer->router);

                    if ($mikrotik->enableUser($customer)) {
                        Log::info("Successfully enabled MikroTik user for customer {$customer->id} after payment {$payment->id}");
                    } else {
                        Log::warning("Payment successful but MikroTik enable failed for customer {$customer->id} (payment {$payment->id}). Router may be unreachable; scheduling retries.", [
                            'router_id' => $customer->router_id,
                        ]);

                        $chainKey = 'router-provision-retry-chain:'.$customer->id.':'.$payment->id;
                        if (Cache::add($chainKey, 1, now()->addMinutes(70))) {
                            RetryRouterProvisioning::dispatch($customer->id, $payment->id, 1)
                                ->delay(now()->addMinutes(5));
                        }
                    }
                }

                if ($customer->status !== 'active') {
                    $customer->activate();
                }

                if ($invoice && $invoice->subscription_id) {
                    $subscription = Subscription::query()->find($invoice->subscription_id);

                    if ($subscription) {
                        if ($customer->connection_type === 'hotspot') {
                            if ($subscription->status === 'suspended' || $subscription->isExpired()) {
                                $subscription->update([
                                    'status' => 'active',
                                    'start_date' => now(),
                                    'end_date' => now()->addDays($subscription->plan->duration_days ?? 30),
                                    'renewal_date' => now()->addDays($subscription->plan->duration_days ?? 30),
                                ]);
                                Log::info("Activated hotspot subscription {$subscription->id} for customer {$customer->id} after payment");
                            }
                        } else {
                            if ($subscription->isExpired() || $subscription->end_date <= now()->addDays(7)) {
                                $subscription->renew();
                                Log::info("Renewed subscription {$subscription->id} for customer {$customer->id}");
                            }
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
