<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\HotspotSession;
use App\Models\IspPaymentMethod;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CheckoutController extends Controller
{
    /** Public, short-lived status check for the hotspot buyer who started this payment. */
    public function status(Request $request, Payment $payment)
    {
        $payment->refresh();

        // Daraja cancellation callbacks can be delayed or missed by captive
        // portal hosts. While pending, query Safaricom directly so the buyer
        // sees a cancellation even when the callback cannot reach this app.
        if (
            $payment->status === 'pending'
            && $payment->payment_method === 'mpesa'
            && $payment->transaction_id
            && ! str_starts_with((string) $payment->transaction_id, 'DEV-')
        ) {
            try {
                $query = (new \App\Services\MpesaService($payment->isp))
                    ->verifyPayment((string) $payment->transaction_id);
                $outcome = \App\Services\MpesaService::interpretStkQueryResult($query);

                if (in_array($outcome, ['cancelled', 'failed'], true)) {
                    $payment->update([
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'stk_query_response' => $query,
                            'failure_reason' => $query['ResultDesc'] ?? 'The M-PESA request was cancelled or declined.',
                        ]),
                    ]);
                    $payment->markAsFailed();
                    \App\Models\PaymentTransactionLog::log([
                        'payment_id' => $payment->id,
                        'isp_id' => $payment->isp_id,
                        'transaction_type' => 'status_query',
                        'payment_method' => 'mpesa',
                        'payment_reference' => $payment->payment_reference,
                        'transaction_id' => $payment->transaction_id,
                        'status' => 'failed',
                        'amount' => $payment->amount,
                        'response_data' => $query,
                    ]);
                } elseif ($outcome === 'paid') {
                    $payment->update([
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'stk_query_response' => $query,
                            'stk_query_confirmed_at' => now()->toIso8601String(),
                        ]),
                    ]);
                    $payment->markAsCompleted();
                    app(\App\Services\PaymentCompletionService::class)
                        ->processTenantPaymentSuccess($payment->fresh());
                }

                $payment->refresh();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Hotspot M-Pesa status query failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $status = $payment->status;
        $message = match ($status) {
            'completed' => 'Payment received.',
            'failed', 'cancelled' => $payment->metadata['failure_reason'] ?? 'The payment was cancelled or declined.',
            default => 'Waiting for M-Pesa confirmation.',
        };

        return response()->json([
            'status' => $status,
            'message' => $message,
            'access_url' => $status === 'completed'
                ? URL::temporarySignedRoute(
                    'hotspot.checkout.access', now()->addMinutes(10), ['payment' => $payment->id]
                )
                : null,
        ]);
    }

    public function cancel(Payment $payment)
    {
        if ($payment->payment_method === 'stripe' && $payment->status === 'pending') {
            $payment->markAsFailed();
        }
        return redirect()->route('hotspot.splash')->with('message', 'Checkout was cancelled. You can try again when ready.');
    }

    /**
     * Handle customer checkout completion from hotspot after successful payment.
     *
     * Provider return URL. The provider's server-side status is verified here
     * before access is enabled; the browser redirect alone is never trusted.
     */
    public function access(Request $request, Payment $payment)
    {
        if (! $request->hasValidSignatureWhileIgnoring(['session_id'])) {
            abort(403, 'This checkout link is invalid or has expired.');
        }
        if ($payment->payment_method === 'stripe' && ! $this->confirmStripeSession($request, $payment)) {
            abort(403, 'Payment could not be verified.');
        }
        // 1. Ensure payment is completed
        if ($payment->status !== 'completed') {
            if ($payment->payment_method !== 'stripe') {
                abort(403, 'Payment not completed.');
            }
            app(\App\Services\PaymentCompletionService::class)->markAsCompleted($payment);
        }

        $customer = $payment->customer;

        if (!$customer) {
            abort(404, 'Customer not found for this payment.');
        }

        // 2. ISP and device binding. The MAC was captured from RouterOS when
        // the purchase began and is applied to the RouterOS user itself.
        $router = $customer->router;

        if ($router && $router->isp_id !== $payment->isp_id) {
            abort(403, 'ISP mismatch for this payment.');
        }

        // 3. Provision/enable the paid plan synchronously. Do not display
        // success when RouterOS could not be configured.
        $routerReady = false;
        if ($router && $customer->connection_type === 'hotspot') {
            try {
                $mikrotik = new MikroTikService($router);
                $routerReady = $mikrotik->activateHotspotUser($customer);
            } catch (\Throwable $e) {
                Log::error('Failed to activate hotspot user during checkout', [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $routerReady) {
            return response()->view('hotspot.access-granted', [
                'customer' => $customer->only(['id', 'username', 'connection_type']),
                'payment' => $payment->only(['id', 'status', 'amount', 'payment_method', 'processed_at']),
                'routerReady' => false,
                'routerLoginUrl' => null,
                'hotspotPassword' => null,
            ], 503);
        }

        return view('hotspot.access-granted', [
            'customer' => $customer->only(['id', 'username', 'connection_type']),
            'payment' => $payment->only(['id', 'status', 'amount', 'payment_method', 'processed_at']),
            'routerReady' => true,
            'routerLoginUrl' => $customer->hotspot_login_url,
            'hotspotPassword' => $customer->hotspot_password,
        ]);
    }

    private function confirmStripeSession(Request $request, Payment $payment): bool
    {
        $sessionId = $request->query('session_id');
        if (! is_string($sessionId) || ! str_starts_with($sessionId, 'cs_')) return false;
        $method = IspPaymentMethod::forIsp($payment->isp_id)
            ->where('provider', 'stripe')->first();
        $secret = $method?->getCredential('secret_key');
        if (! $secret) return false;
        try {
            $response = Http::withToken($secret)->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId));
            $session = $response->json();
            return $response->successful()
                && ($session['id'] ?? null) === ($payment->metadata['stripe_session_id'] ?? null)
                && (string) ($session['client_reference_id'] ?? '') === (string) $payment->id
                && (int) ($session['amount_total'] ?? 0) === (int) round((float) $payment->amount * 100)
                && ($session['payment_status'] ?? null) === 'paid';
        } catch (\Throwable $e) {
            Log::warning('Stripe checkout verification failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}






