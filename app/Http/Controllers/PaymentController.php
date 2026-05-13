<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\IspPaymentMethod;
use App\Models\PaymentTransactionLog;
use App\Services\MpesaService;
use App\Services\PaymentCompletionService;
use App\Services\SelcomService;
use App\Services\MikroTikService;
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

    /**
     * Initiate payment
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|in:mpesa,selcom,manual',
            'phone_number' => 'required_if:payment_method,mpesa,selcom',
        ]);

        $invoice = Invoice::with('customer', 'isp')->findOrFail($request->invoice_id);

        // Validate phone number format for MPesa (Kenya)
        if ($request->payment_method === 'mpesa') {
            $phoneNumber = $request->phone_number ?? $invoice->customer->phone_number;
            // Format phone number to 254XXXXXXXXX
            $phoneNumber = preg_replace('/^0/', '254', $phoneNumber);
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            if (!preg_match('/^254[0-9]{9}$/', $phoneNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format. Please use format: 254XXXXXXXXX',
                ], 400);
            }
        }

        $payment = Payment::create([
            'isp_id' => $invoice->isp_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'payment_method' => $request->payment_method,
            'amount' => $invoice->total_amount,
            'phone_number' => $phoneNumber ?? ($request->phone_number ?? $invoice->customer->phone_number),
            'status' => 'pending',
        ]);

        // Log payment initiation
        PaymentTransactionLog::log([
            'payment_id' => $payment->id,
            'isp_id' => $payment->isp_id,
            'transaction_type' => 'initiated',
            'payment_method' => $request->payment_method,
            'payment_reference' => $payment->payment_reference,
            'status' => 'pending',
            'amount' => $payment->amount,
            'request_data' => $request->all(),
        ]);

        // Log payment attempt in ActivityLog
        \App\Services\ActivityLogService::logAction(
            'payment_attempt',
            "Payment attempt initiated: {$payment->payment_reference} for invoice {$invoice->invoice_number} via {$request->payment_method}",
            [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_method' => $request->payment_method,
                'amount' => $payment->amount,
            ]
        );

        if ($request->payment_method === 'mpesa') {
            $mpesaService = new MpesaService($invoice->isp);
            $result = $mpesaService->initiateStkPush($payment);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'MPesa STK Push initiated. Please check your phone.',
                    'payment' => $payment,
                    'checkout_request_id' => $result['checkout_request_id'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        if ($request->payment_method === 'selcom') {
            $selcomService = new SelcomService($invoice->isp);
            $result = $selcomService->initiatePayment($payment);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'payment_url' => $result['payment_url'],
                    'payment' => $payment,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        // Manual payment
        return response()->json([
            'success' => true,
            'message' => 'Payment recorded. Awaiting confirmation.',
            'payment' => $payment,
        ]);
    }

    /**
     * MPesa callback webhook - Secure webhook handler
     */
    public function mpesaCallback(Request $request, ?string $webhook_token = null)
    {
        try {
            $data = $request->all();
            $ipAddress = $request->ip();
            
            // Security: Validate IP address (Safaricom IP ranges)
            // Only validate in production, allow all in sandbox for testing
            $environment = config('services.mpesa.environment', 'sandbox');
            if ($environment === 'production') {
                $allowedIPs = config('services.mpesa.allowed_ips', []);
                if (!empty($allowedIPs) && !in_array($ipAddress, $allowedIPs)) {
                    // Log unauthorized attempt
                    PaymentTransactionLog::log([
                        'transaction_type' => 'webhook',
                        'payment_method' => 'mpesa',
                        'status' => 'failed',
                        'request_data' => $data,
                        'ip_address' => $ipAddress,
                        'error_message' => 'Unauthorized IP address: ' . $ipAddress,
                    ]);
                    
                    Log::warning('MPesa webhook from unauthorized IP: ' . $ipAddress);
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }
            
            // Log all webhook attempts for audit
            PaymentTransactionLog::log([
                'isp_id' => null,
                'transaction_type' => 'webhook',
                'payment_method' => 'mpesa',
                'status' => 'pending',
                'request_data' => $data,
                'ip_address' => $ipAddress,
            ]);

            Log::info('MPesa callback received', [
                'ip' => $ipAddress,
                'data' => $data
            ]);

            // Validate webhook structure
            if (!isset($data['Body']['stkCallback'])) {
                \App\Models\PaymentTransactionLog::log([
                    'transaction_type' => 'webhook',
                    'payment_method' => 'mpesa',
                    'status' => 'failed',
                    'request_data' => $data,
                    'error_message' => 'Invalid webhook structure',
                ]);
                return response()->json(['error' => 'Invalid callback structure'], 400);
            }

            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
            $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? null;

            if (!$checkoutRequestId) {
                \App\Models\PaymentTransactionLog::log([
                    'transaction_type' => 'webhook',
                    'payment_method' => 'mpesa',
                    'status' => 'failed',
                    'request_data' => $data,
                    'error_message' => 'Missing CheckoutRequestID',
                ]);
                return response()->json(['error' => 'Invalid callback'], 400);
            }

            $payment = Payment::findByMpesaCheckoutRequestId($checkoutRequestId);

            if (!$payment) {
                Log::warning('Payment not found for checkout request: ' . $checkoutRequestId);
                \App\Models\PaymentTransactionLog::log([
                    'transaction_type' => 'webhook',
                    'payment_method' => 'mpesa',
                    'transaction_id' => $checkoutRequestId,
                    'status' => 'failed',
                    'request_data' => $data,
                    'error_message' => 'Payment not found for checkout request: ' . $checkoutRequestId,
                ]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            if ($webhook_token !== null && $webhook_token !== '') {
                $tokenValid = IspPaymentMethod::query()
                    ->where('isp_id', $payment->isp_id)
                    ->where('provider', 'daraja')
                    ->where('webhook_token', $webhook_token)
                    ->exists();

                if (! $tokenValid) {
                    PaymentTransactionLog::log([
                        'payment_id' => $payment->id,
                        'isp_id' => $payment->isp_id,
                        'transaction_type' => 'webhook',
                        'payment_method' => 'mpesa',
                        'status' => 'failed',
                        'request_data' => $data,
                        'ip_address' => $ipAddress,
                        'error_message' => 'Callback token does not match this ISP payment method',
                    ]);

                    return response()->json(['error' => 'Forbidden'], 403);
                }
            }

            // Log webhook received for this payment
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'webhook',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'transaction_id' => $checkoutRequestId,
                'status' => $resultCode == 0 ? 'completed' : 'failed',
                'amount' => $payment->amount,
                'request_data' => $data,
                'ip_address' => $ipAddress,
            ]);

            if ($resultCode == 0) {
                // Payment successful - Extract receipt number
                $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
                $mpesaReceiptNumber = null;
                $amountPaid = null;

                foreach ($callbackMetadata as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceiptNumber = $item['Value'];
                    }
                    if ($item['Name'] === 'Amount') {
                        $amountPaid = $item['Value'];
                    }
                }

                if (! $mpesaReceiptNumber) {
                    \App\Models\PaymentTransactionLog::log([
                        'payment_id' => $payment->id,
                        'isp_id' => $payment->isp_id,
                        'transaction_type' => 'webhook',
                        'payment_method' => 'mpesa',
                        'payment_reference' => $payment->payment_reference,
                        'transaction_id' => $checkoutRequestId,
                        'status' => 'failed',
                        'amount' => $payment->amount,
                        'request_data' => $data,
                        'error_message' => 'Missing MpesaReceiptNumber in callback metadata',
                    ]);

                    return response()->json(['error' => 'Invalid callback metadata'], 400);
                }

                // Verify amount matches
                if ($amountPaid && abs($amountPaid - $payment->amount) > 0.01) {
                    \App\Models\PaymentTransactionLog::log([
                        'payment_id' => $payment->id,
                        'isp_id' => $payment->isp_id,
                        'transaction_type' => 'webhook',
                        'payment_method' => 'mpesa',
                        'payment_reference' => $payment->payment_reference,
                        'transaction_id' => $mpesaReceiptNumber,
                        'status' => 'failed',
                        'amount' => $payment->amount,
                        'request_data' => $data,
                        'error_message' => 'Amount mismatch. Expected: ' . $payment->amount . ', Received: ' . $amountPaid,
                    ]);
                    return response()->json(['error' => 'Amount mismatch'], 400);
                }

                $finalizedInTransaction = false;

                try {
                    DB::transaction(function () use ($payment, $mpesaReceiptNumber, $checkoutRequestId, $data, &$finalizedInTransaction) {
                        $locked = Payment::query()->lockForUpdate()->find($payment->id);

                        if (! $locked) {
                            return;
                        }

                        if ($locked->status === 'completed') {
                            Log::info('MPesa STK callback ignored: payment already completed', [
                                'payment_id' => $locked->id,
                                'checkout_request_id' => $checkoutRequestId,
                            ]);

                            return;
                        }

                        $locked->update([
                            'transaction_id' => $mpesaReceiptNumber,
                            'metadata' => array_merge($locked->metadata ?? [], [
                                'mpesa_checkout_request_id' => $checkoutRequestId,
                                'mpesa_receipt' => $mpesaReceiptNumber,
                                'webhook_data' => $data,
                                'webhook_received_at' => now()->toIso8601String(),
                            ]),
                        ]);

                        $locked->markAsCompleted();

                        \App\Models\PaymentTransactionLog::log([
                            'payment_id' => $locked->id,
                            'isp_id' => $locked->isp_id,
                            'transaction_type' => 'webhook',
                            'payment_method' => 'mpesa',
                            'payment_reference' => $locked->payment_reference,
                            'transaction_id' => $mpesaReceiptNumber,
                            'status' => 'completed',
                            'amount' => $locked->amount,
                            'response_data' => ['message' => 'Payment processed successfully'],
                        ]);

                        $finalizedInTransaction = true;
                    });
                } catch (QueryException $e) {
                    $mysqlErr = $e->errorInfo[1] ?? null;
                    $sqlState = $e->errorInfo[0] ?? null;
                    $isDuplicate = $mysqlErr === 1062
                        || $sqlState === '23000'
                        || stripos((string) $e->getMessage(), 'duplicate') !== false;

                    if ($isDuplicate) {
                        Log::warning('MPesa callback: duplicate transaction_id (idempotent)', [
                            'checkout_request_id' => $checkoutRequestId,
                            'mpesa_receipt' => $mpesaReceiptNumber,
                            'payment_id' => $payment->id,
                        ]);

                        PaymentTransactionLog::log([
                            'payment_id' => $payment->id,
                            'isp_id' => $payment->isp_id,
                            'transaction_type' => 'webhook',
                            'payment_method' => 'mpesa',
                            'payment_reference' => $payment->payment_reference,
                            'transaction_id' => $mpesaReceiptNumber,
                            'status' => 'completed',
                            'amount' => $payment->amount,
                            'request_data' => $data,
                            'error_message' => 'Duplicate MpesaReceiptNumber ignored (unique constraint)',
                        ]);

                        return response()->json(['success' => true]);
                    }

                    throw $e;
                }

                if ($finalizedInTransaction) {
                    $fresh = $payment->fresh();
                    if ($fresh && $fresh->isPlatformSaasPayment()) {
                        app(\App\Services\PlatformSaasBillingService::class)->onMpesaPaymentSucceeded($fresh);
                    } elseif ($fresh && $fresh->isSaasOnboardingVerification() && $fresh->isp_id) {
                        \App\Models\ISP::query()->whereKey($fresh->isp_id)->update([
                            'saas_payment_verified_at' => now(),
                        ]);
                    } elseif ($fresh) {
                        $this->paymentCompletionService->processTenantPaymentSuccess($fresh);
                    }
                }

                return response()->json(['success' => true]);
            } else {
                // Payment failed
                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'webhook_data' => $data,
                        'failure_reason' => $resultDesc,
                    ]),
                ]);
                $payment->markAsFailed();
                
                \App\Models\PaymentTransactionLog::log([
                    'payment_id' => $payment->id,
                    'isp_id' => $payment->isp_id,
                    'transaction_type' => 'webhook',
                    'payment_method' => 'mpesa',
                    'payment_reference' => $payment->payment_reference,
                    'transaction_id' => $checkoutRequestId,
                    'status' => 'failed',
                    'amount' => $payment->amount,
                    'request_data' => $data,
                    'error_message' => $resultDesc,
                ]);
                
                return response()->json(['success' => false, 'message' => $resultDesc]);
            }
        } catch (\Exception $e) {
            Log::error('MPesa callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            \App\Models\PaymentTransactionLog::log([
                'transaction_type' => 'webhook',
                'payment_method' => 'mpesa',
                'status' => 'failed',
                'request_data' => $request->all(),
                'error_message' => 'Exception: ' . $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * MPesa timeout callback webhook
     */
    public function mpesaTimeout(Request $request)
    {
        try {
            $data = $request->all();
            $ipAddress = $request->ip();
            
            Log::info('MPesa timeout callback received', [
                'ip' => $ipAddress,
                'data' => $data
            ]);

            // Log timeout callback
            PaymentTransactionLog::log([
                'isp_id' => null,
                'transaction_type' => 'webhook_timeout',
                'payment_method' => 'mpesa',
                'status' => 'failed',
                'request_data' => $data,
                'ip_address' => $ipAddress,
                'error_message' => 'Payment request timed out',
            ]);

            // Extract checkout request ID if available
            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            
            if ($checkoutRequestId) {
                $payment = Payment::findByMpesaCheckoutRequestId($checkoutRequestId);
                
                if ($payment && $payment->status === 'pending') {
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'timeout_callback' => $data,
                            'timeout_at' => now()->toIso8601String(),
                        ]),
                    ]);

                    Log::info("Payment {$payment->id} marked as failed due to timeout");
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('MPesa timeout callback error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Selcom callback webhook
     */
    public function selcomCallback(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('Selcom callback received', $data);

            $payment = Payment::where('payment_reference', $data['order_id'] ?? '')
                ->orWhere('transaction_id', $data['order_id'] ?? '')
                ->first();
            
            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 404);
            }
            
            $selcomService = new SelcomService($payment->isp);

            if (!$selcomService->handleWebhook($data)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $orderId = $data['order_id'] ?? null;
            $status = $data['status'] ?? null;

            if (!$orderId) {
                return response()->json(['error' => 'Invalid callback'], 400);
            }

            if ($status === 'COMPLETED' || $status === 'PAID') {
                $finalized = false;

                DB::transaction(function () use ($payment, $data, &$finalized) {
                    $locked = Payment::query()->lockForUpdate()->find($payment->id);

                    if (! $locked || $locked->status === 'completed') {
                        return;
                    }

                    $locked->update([
                        'transaction_id' => $data['transaction_id'] ?? $data['order_id'] ?? null,
                        'metadata' => array_merge($locked->metadata ?? [], [
                            'selcom_webhook' => $data,
                        ]),
                    ]);

                    $locked->markAsCompleted();
                    $finalized = true;
                });

                if ($finalized) {
                    $this->paymentCompletionService->processTenantPaymentSuccess($payment->fresh());
                }

                return response()->json(['success' => true]);
            } else {
                $payment->markAsFailed();
                return response()->json(['success' => false, 'message' => 'Payment failed']);
            }
        } catch (\Exception $e) {
            Log::error('Selcom callback error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Activate customer on router after payment (legacy method, kept for compatibility)
     */
    protected function activateCustomerOnRouter(Customer $customer): void
    {
        if (! $customer->router || (! $customer->mikrotik_user_id && ! $customer->username)) {
            return;
        }

        try {
            $mikrotik = new MikroTikService($customer->router);
            $mikrotik->enableUser($customer);
            $customer->activate();
        } catch (\Exception $e) {
            Log::error("Failed to activate customer {$customer->id} on router: " . $e->getMessage());
        }
    }

    /**
     * Manual payment confirmation
     */
    public function confirmManual(Request $request, Payment $payment)
    {
        $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if ($request->confirm) {
            DB::transaction(function () use ($payment) {
                $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                if ($locked->status !== 'completed') {
                    $locked->markAsCompleted();
                }
            });

            $this->paymentCompletionService->processTenantPaymentSuccess($payment->fresh(), true);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and customer activated',
            ]);
        }

        $payment->markAsFailed();
        return response()->json([
            'success' => false,
            'message' => 'Payment rejected',
        ]);
    }
}
