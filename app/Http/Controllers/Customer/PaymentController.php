<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Services\PaymentCompletionService;
use App\Services\PaymentGatewayService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentCompletionService $paymentCompletionService
    ) {
    }

    public function checkout(Request $request)
    {
        $customer = auth()->user()->customer;
        $planId = $request->get('plan_id');
        
        if (!$planId) {
            return redirect()->route('customer.plans')->with('error', 'Please select a plan first');
        }

        $plan = Plan::findOrFail($planId);
        
        // Verify plan belongs to customer's ISP
        if ($plan->isp_id !== $customer->isp_id) {
            abort(403, 'Invalid plan selection');
        }

        // Get available payment methods
        $paymentService = new PaymentGatewayService($customer->isp_id);
        $availableMethods = collect($paymentService->getAvailableMethods());

        return view('customer.payment-checkout', compact('customer', 'plan', 'availableMethods'));
    }

    public function processPayment(Request $request)
    {
        $customer = auth()->user()->customer;
        $plan = Plan::findOrFail($request->plan_id);
        
        // Verify plan belongs to customer's ISP
        if ($plan->isp_id !== $customer->isp_id) {
            return back()->with('error', 'Invalid plan selection');
        }

        $paymentProvider = $request->payment_provider;
        $amount = $request->amount;

        // Generate payment reference
        $paymentReference = 'PAY-' . strtoupper(uniqid());

        $paymentData = [
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'payment_reference' => $paymentReference,
            'phone_number' => $request->phone_number ?? $customer->phone_number,
            'customer_email' => $customer->user->email,
            'customer_name' => $customer->full_name,
            'currency' => $customer->isp->currency ?? 'KES',
            'description' => "Payment for {$plan->name} - {$plan->billing_cycle}",
            'redirect_url' => route('payment.success'),
            'metadata' => [
                'plan_id' => $plan->id,
                'customer_id' => $customer->id,
                'billing_cycle' => $plan->billing_cycle,
                'plan_name' => $plan->name
            ]
        ];

        // Process payment
        $paymentService = new PaymentGatewayService($customer->isp_id);
        $result = $paymentService->initiatePayment($paymentProvider, $paymentData);

        if ($result['success']) {
            // Store payment result in session for success page
            session(['payment_result' => $result]);

            // Handle redirect payments
            if (isset($result['redirect_url'])) {
                return redirect($result['redirect_url']);
            }

            // For M-Pesa and similar, show processing page
            if ($paymentProvider === 'mpesa') {
                return redirect()->route('payment.processing', [
                    'payment_id' => $result['payment_id'],
                    'reference' => $result['payment_reference']
                ]);
            }

            // Default success redirect
            return redirect()->route('payment.success')->with('success', 'Payment initiated successfully');
        }

        return back()->with('error', $result['message'] ?? 'Payment failed');
    }

    public function processing(Request $request)
    {
        $paymentId = $request->payment_id;
        $reference = $request->reference;

        return view('customer.payment-processing', compact('paymentId', 'reference'));
    }

    public function checkStatus(Request $request)
    {
        $paymentId = $request->payment_id;
        $payment = \App\Models\Payment::find($paymentId);

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found']);
        }

        // Check payment status based on provider
        if ($payment->payment_method === 'mpesa' && $payment->transaction_id) {
            $customer = auth()->user()->customer;
            $mpesaService = new \App\Services\MpesaService($customer->isp);
            $status = $mpesaService->verifyPayment($payment->transaction_id);

            // Update payment status if completed
            if (isset($status['ResultCode']) && $status['ResultCode'] === '0') {
                $payment->markAsCompleted();
                if (! $payment->isPlatformSaasPayment()) {
                    $this->paymentCompletionService->processTenantPaymentSuccess($payment->fresh());
                }
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'redirect' => route('payment.success')
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'message' => $this->getStatusMessage($payment->status)
        ]);
    }

    public function success(Request $request)
    {
        $paymentResult = session('payment_result');
        
        if (!$paymentResult) {
            return redirect()->route('customer.dashboard')->with('info', 'Payment session expired');
        }

        // Clear payment result from session
        session()->forget('payment_result');

        return view('customer.payment-success', compact('paymentResult'));
    }

    public function callback(Request $request, string $provider)
    {
        Log::info("Payment callback received from {$provider}", $request->all());

        try {
            switch ($provider) {
                case 'mpesa':
                    return $this->handleMpesaCallback($request);
                case 'paypal':
                    return $this->handlePaypalCallback($request);
                case 'flutterwave':
                    return $this->handleFlutterwaveCallback($request);
                default:
                    Log::warning("Unsupported callback provider: {$provider}");
                    return response()->json(['status' => 'error'], 400);
            }
        } catch (\Exception $e) {
            Log::error("Payment callback error for {$provider}: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function handleMpesaCallback(Request $request)
    {
        $callbackData = $request->all();
        
        // Extract payment info from callback
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? null;
        $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        
        if ($resultCode === '0') {
            // Payment successful
            $metadata = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $mpesaReceipt = null;
            $amount = null;
            
            foreach ($metadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesaReceipt = $item['Value'];
                }
                if ($item['Name'] === 'Amount') {
                    $amount = $item['Value'];
                }
            }

            // Find payment by checkout request ID (or metadata after receipt replaced transaction_id)
            $payment = \App\Models\Payment::findByMpesaCheckoutRequestId($checkoutRequestId);

            if ($payment) {
                if ($amount === null || (int) round((float) $amount) !== (int) round((float) $payment->amount)) {
                    Log::warning('M-Pesa callback amount did not match the invoice; access was not activated.', [
                        'payment_id' => $payment->id,
                        'expected_amount' => $payment->amount,
                        'callback_amount' => $amount,
                        'checkout_request_id' => $checkoutRequestId,
                    ]);
                    $payment->update([
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'mpesa_amount_mismatch' => [
                                'expected' => (float) $payment->amount,
                                'received' => $amount,
                                'recorded_at' => now()->toIso8601String(),
                            ],
                        ]),
                    ]);

                    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Received']);
                }

                $finalized = false;

                try {
                    DB::transaction(function () use ($payment, $mpesaReceipt, $callbackData, $checkoutRequestId, &$finalized) {
                        $locked = \App\Models\Payment::query()->lockForUpdate()->find($payment->id);
                        if (! $locked || $locked->status === 'completed') {
                            return;
                        }

                        $locked->update([
                            'transaction_id' => $mpesaReceipt,
                            'metadata' => array_merge($locked->metadata ?? [], [
                                'mpesa_receipt' => $mpesaReceipt,
                                'mpesa_checkout_request_id' => $checkoutRequestId,
                                'callback_data' => $callbackData,
                            ]),
                        ]);

                        $locked->markAsCompleted();
                        $finalized = true;
                    });
                } catch (QueryException $e) {
                    $mysqlErr = $e->errorInfo[1] ?? null;
                    $sqlState = $e->errorInfo[0] ?? null;
                    $isDuplicate = $mysqlErr === 1062
                        || $sqlState === '23000'
                        || stripos((string) $e->getMessage(), 'duplicate') !== false;

                    if ($isDuplicate) {
                        Log::warning('Customer MPesa callback: duplicate transaction_id ignored', [
                            'payment_id' => $payment->id,
                            'checkout_request_id' => $checkoutRequestId,
                        ]);

                        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
                    }

                    throw $e;
                }

                if ($finalized) {
                    $fresh = $payment->fresh();
                    if ($fresh && ! $fresh->isPlatformSaasPayment()) {
                        $this->paymentCompletionService->processTenantPaymentSuccess($fresh);
                    }
                }
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    private function handlePaypalCallback(Request $request)
    {
        // PayPal webhook handling would go here
        Log::info('PayPal callback received', $request->all());
        return response()->json(['status' => 'success']);
    }

    private function handleFlutterwaveCallback(Request $request)
    {
        // Flutterwave webhook handling would go here
        Log::info('Flutterwave callback received', $request->all());
        return response()->json(['status' => 'success']);
    }

    private function getStatusMessage(string $status): string
    {
        $messages = [
            'pending' => 'Payment is being processed...',
            'completed' => 'Payment completed successfully!',
            'failed' => 'Payment failed. Please try again.',
            'cancelled' => 'Payment was cancelled.',
            'timeout' => 'Payment timed out. Please try again.'
        ];

        return $messages[$status] ?? 'Payment status unknown.';
    }
}
