<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\UserConnectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandlePaymentWebhook
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if this is a successful payment completion
        if ($this->isSuccessfulPayment($request)) {
            $this->processPaymentCompletion($request);
        }

        return $response;
    }

    /**
     * Check if the request indicates a successful payment
     */
    private function isSuccessfulPayment(Request $request): bool
    {
        // Check for payment completion indicators
        return $request->has('payment_id') && 
               $request->input('status') === 'completed' ||
               $request->routeIs('payment.success') ||
               $request->has('payment_reference');
    }

    /**
     * Process payment completion and reconnect users
     */
    private function processPaymentCompletion(Request $request): void
    {
        try {
            $paymentId = $request->input('payment_id');
            $paymentReference = $request->input('payment_reference');

            if (!$paymentId && !$paymentReference) {
                return;
            }

            // Find the payment
            $payment = Payment::where('id', $paymentId)
                ->orWhere('payment_reference', $paymentReference)
                ->first();

            if (!$payment || $payment->status !== 'completed') {
                return;
            }

            // Find the customer
            $customer = Customer::find($payment->customer_id);
            if (!$customer) {
                return;
            }

            // Check if customer has active subscription
            $activeSubscription = $customer->subscriptions()
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now())
                ->first();

            if (!$activeSubscription) {
                return;
            }

            // Reconnect the user
            $this->reconnectUser($customer, $activeSubscription);

        } catch (\Exception $e) {
            Log::error("Error processing payment webhook: " . $e->getMessage());
        }
    }

    /**
     * Reconnect user after successful payment
     */
    private function reconnectUser(Customer $customer, Subscription $subscription): void
    {
        try {
            if (!$customer->router) {
                Log::warning("Customer {$customer->id} has no router configured");
                return;
            }

            $connectionService = new UserConnectionService($customer->router);
            
            // Reconnect user
            $result = $connectionService->reconnectUser($customer, $subscription);
            
            if ($result) {
                // Apply bandwidth limits
                $connectionService->applyBandwidthLimits($customer, $subscription);
                
                // Update customer status
                $customer->update([
                    'status' => 'active',
                    'connection_status' => 'connected',
                    'reconnected_at' => now(),
                ]);

                Log::info("Automatically reconnected user after payment: {$customer->username}");
            } else {
                Log::error("Failed to reconnect user after payment: {$customer->username}");
            }

        } catch (\Exception $e) {
            Log::error("Error reconnecting user {$customer->username}: " . $e->getMessage());
        }
    }
}
