<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $invoiceId = $request->get('invoice_id');
        $invoice = null;

        if ($invoiceId) {
            $invoice = \App\Models\Invoice::where('isp_id', $ispId)
                ->with('customer')
                ->findOrFail($invoiceId);
        }

        $invoices = \App\Models\Invoice::where('isp_id', $ispId)
            ->where('status', '!=', 'paid')
            ->with('customer')
            ->latest()
            ->get();

        return view('isp.payments.create', compact('invoice', 'invoices'));
    }

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Payment::where('isp_id', $ispId)
            ->with(['customer', 'invoice']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by paid date (processed_at)
        if ($request->filled('date_from')) {
            $query->whereDate('processed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('processed_at', '<=', $request->date_to);
        }

        // Filter by customer/search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest('processed_at')->latest()->paginate(20)->appends($request->query());

        return view('isp.payments.index', compact('payments'));
    }

    /**
     * Manually activate internet access for this payment's customer.
     * Intended for completed manual (cash) payments.
     */
    public function activateInternet(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $customer = $payment->customer;

        if (! $customer || ! $customer->router) {
            return back()->with('error', 'Customer or router not found for this payment.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);

            if ($customer->connection_type === 'hotspot') {
                $mikrotik->activateHotspotUser($customer);
            } else {
                $mikrotik->enableUser($customer);
            }

            $customer->activate();

            // Log manual activation action
            \App\Services\ActivityLogService::logAction(
                'manual_activation',
                "Manually activated internet for customer {$customer->full_name} (ID: {$customer->id}) via payment {$payment->payment_reference}",
                [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'connection_type' => $customer->connection_type,
                ]
            );

            return back()->with('success', 'Internet activated manually for this customer.');
        } catch (\Exception $e) {
            // Log failed activation
            \App\Services\ActivityLogService::logAction(
                'manual_activation',
                "Failed to manually activate internet for customer {$customer->full_name} (ID: {$customer->id}) via payment {$payment->payment_reference}: ".$e->getMessage(),
                [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );

            return back()->with('error', 'Failed to activate internet: '.$e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $payment->load(['customer', 'invoice']);

        return view('isp.payments.show', compact('payment'));
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|in:mpesa,selcom,manual',
            'phone_number' => 'required_if:payment_method,mpesa',
        ]);

        $invoice = \App\Models\Invoice::with('customer', 'isp')->findOrFail($request->invoice_id);

        // Verify invoice belongs to ISP
        if ($invoice->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        // Check if invoice is already paid
        if ($invoice->status === 'paid') {
            return back()->with('error', 'This invoice has already been paid.');
        }

        // Format phone number for MPesa
        $phoneNumber = null;
        if ($request->payment_method === 'mpesa') {
            $phoneNumber = $request->phone_number ?? $invoice->customer->phone_number;
            $phoneNumber = preg_replace('/^0/', '254', $phoneNumber);
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            if (! preg_match('/^254[0-9]{9}$/', $phoneNumber)) {
                return back()->with('error', 'Invalid phone number format. Please use format: 254XXXXXXXXX');
            }
        }

        $payment = \App\Models\Payment::create([
            'isp_id' => $invoice->isp_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'payment_method' => $request->payment_method,
            'amount' => $invoice->total_amount,
            'phone_number' => $phoneNumber ?? ($request->phone_number ?? $invoice->customer->phone_number),
            'status' => 'pending',
        ]);

        // Log payment initiation
        \App\Models\PaymentTransactionLog::log([
            'payment_id' => $payment->id,
            'isp_id' => $payment->isp_id,
            'transaction_type' => 'initiated',
            'payment_method' => $request->payment_method,
            'payment_reference' => $payment->payment_reference,
            'status' => 'pending',
            'amount' => $payment->amount,
            'request_data' => $request->all(),
        ]);

        if ($request->payment_method === 'mpesa') {
            $mpesaService = new \App\Services\MpesaService($invoice->isp);
            $result = $mpesaService->initiateStkPush($payment);

            if ($result['success']) {
                return redirect()->route('isp.payments.show', $payment->id)
                    ->with('success', 'MPesa STK Push initiated. Please check your phone to complete the payment.');
            }

            return back()->with('error', $result['message'] ?? 'Failed to initiate MPesa payment.');
        }

        return redirect()->route('isp.payments.show', $payment->id)
            ->with('success', 'Payment recorded. Awaiting confirmation.');
    }

    public function checkStatus(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        return response()->json([
            'status' => $payment->status,
            'payment_reference' => $payment->payment_reference,
            'transaction_id' => $payment->transaction_id,
            'processed_at' => $payment->processed_at?->toIso8601String(),
        ]);
    }

    public function transactionLogs(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = \App\Models\PaymentTransactionLog::where('isp_id', $ispId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(20)->appends($request->query());

        return view('isp.payments.transaction-logs', compact('logs'));
    }

    public function showTransactionLog(\App\Models\PaymentTransactionLog $log)
    {
        if ($log->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        return response()->json([
            'transaction_type' => $log->transaction_type,
            'status' => $log->status,
            'amount' => $log->amount,
            'ip_address' => $log->ip_address,
            'error_message' => $log->error_message,
            'request_data' => $log->request_data,
            'response_data' => $log->response_data,
        ]);
    }

    /**
     * Show payments for a specific customer
     */
    public function customerPayments(\App\Models\Customer $customer, Request $request)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $query = Payment::where('customer_id', $customer->id)
            ->with('invoice');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest('processed_at')->latest()->paginate(20)->appends($request->query());

        return view('isp.payments.customer', compact('customer', 'payments'));
    }
}
