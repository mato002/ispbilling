<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IspPaymentMethod;
use App\Models\Payment;
use App\Models\PaymentTransactionLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Match inbound M-Pesa C2B (manual paybill) confirmations to outstanding invoices.
 */
class MpesaC2BReconciliationService
{
    public function __construct(
        protected PaymentCompletionService $paymentCompletionService
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function processConfirmation(IspPaymentMethod $paymentMethod, array $payload, ?string $ipAddress = null): void
    {
        $ispId = (int) $paymentMethod->isp_id;
        $transId = trim((string) ($payload['TransID'] ?? ''));
        $billRef = trim((string) ($payload['BillRefNumber'] ?? ''));
        $msisdn = isset($payload['MSISDN']) ? (string) $payload['MSISDN'] : null;
        $transAmount = (float) ($payload['TransAmount'] ?? 0);

        PaymentTransactionLog::log([
            'isp_id' => $ispId,
            'transaction_type' => 'webhook',
            'payment_method' => 'mpesa',
            'transaction_id' => $transId !== '' ? $transId : null,
            'status' => 'pending',
            'amount' => $transAmount > 0 ? $transAmount : null,
            'request_data' => $payload,
            'ip_address' => $ipAddress,
        ]);

        if ($transId === '') {
            $this->logUnreconciled($ispId, $payload, $ipAddress, 'Missing TransID in C2B confirmation payload');

            return;
        }

        if (Payment::query()->where('transaction_id', $transId)->exists()) {
            Log::info('M-Pesa C2B confirmation ignored: duplicate TransID', [
                'isp_id' => $ispId,
                'trans_id' => $transId,
            ]);

            return;
        }

        $invoice = $this->resolveOutstandingInvoice($ispId, $billRef, $msisdn);

        if (! $invoice) {
            $this->logUnreconciled(
                $ispId,
                $payload,
                $ipAddress,
                'No outstanding invoice matched BillRefNumber or MSISDN'
            );

            return;
        }

        if ($transAmount > 0 && abs($transAmount - (float) $invoice->total_amount) > 0.01) {
            $this->logUnreconciled(
                $ispId,
                $payload,
                $ipAddress,
                'Amount mismatch. Invoice total: '.$invoice->total_amount.', received: '.$transAmount,
                $invoice
            );

            return;
        }

        $this->finalizeMatchedInvoice($invoice, $payload, $ispId, $transId, $msisdn, $transAmount, $ipAddress);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function finalizeMatchedInvoice(
        Invoice $invoice,
        array $payload,
        int $ispId,
        string $transId,
        ?string $msisdn,
        float $transAmount,
        ?string $ipAddress
    ): void {
        $phoneNumber = $this->normalizeKenyanPhone($msisdn) ?? $invoice->customer?->phone_number;
        $amount = $transAmount > 0 ? $transAmount : (float) $invoice->total_amount;
        $payment = null;
        $finalized = false;

        try {
            DB::transaction(function () use ($invoice, $payload, $ispId, $transId, $phoneNumber, $amount, &$payment, &$finalized) {
                $lockedInvoice = Invoice::query()->lockForUpdate()->find($invoice->id);

                if (! $lockedInvoice || ! in_array($lockedInvoice->status, ['pending', 'overdue'], true)) {
                    return;
                }

                $payment = Payment::create([
                    'isp_id' => $ispId,
                    'customer_id' => $lockedInvoice->customer_id,
                    'invoice_id' => $lockedInvoice->id,
                    'payment_method' => 'mpesa',
                    'amount' => $amount,
                    'transaction_id' => $transId,
                    'phone_number' => $phoneNumber,
                    'status' => 'pending',
                    'metadata' => [
                        'source' => 'c2b_manual_paybill',
                        'c2b_payload' => $payload,
                        'mpesa_receipt' => $transId,
                        'webhook_received_at' => now()->toIso8601String(),
                    ],
                ]);

                $payment->markAsCompleted();

                PaymentTransactionLog::log([
                    'payment_id' => $payment->id,
                    'isp_id' => $ispId,
                    'transaction_type' => 'webhook',
                    'payment_method' => 'mpesa',
                    'payment_reference' => $payment->payment_reference,
                    'transaction_id' => $transId,
                    'status' => 'completed',
                    'amount' => $payment->amount,
                    'response_data' => [
                        'message' => 'C2B payment reconciled to invoice '.$lockedInvoice->invoice_number,
                        'invoice_id' => $lockedInvoice->id,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                $finalized = true;
            });
        } catch (QueryException $e) {
            $mysqlErr = $e->errorInfo[1] ?? null;
            $sqlState = $e->errorInfo[0] ?? null;
            $isDuplicate = $mysqlErr === 1062
                || $sqlState === '23000'
                || stripos((string) $e->getMessage(), 'duplicate') !== false;

            if ($isDuplicate) {
                Log::warning('M-Pesa C2B: duplicate transaction_id during finalize (idempotent)', [
                    'isp_id' => $ispId,
                    'trans_id' => $transId,
                ]);

                return;
            }

            throw $e;
        }

        if ($finalized && $payment) {
            $this->paymentCompletionService->markAsCompleted($payment->fresh());
        }
    }

    protected function resolveOutstandingInvoice(int $ispId, string $billRef, ?string $msisdn): ?Invoice
    {
        if ($billRef !== '') {
            $byNumber = Invoice::query()
                ->where('isp_id', $ispId)
                ->where('invoice_number', $billRef)
                ->whereIn('status', ['pending', 'overdue'])
                ->first();

            if ($byNumber) {
                return $byNumber;
            }

            if (preg_match('/^C(\d+)$/i', $billRef, $matches)) {
                $byCustomer = $this->outstandingInvoiceForCustomer($ispId, (int) $matches[1]);
                if ($byCustomer) {
                    return $byCustomer;
                }
            }

            $billRefPhone = $this->normalizeKenyanPhone($billRef);
            if ($billRefPhone) {
                $byBillRefPhone = $this->outstandingInvoiceForPhone($ispId, $billRefPhone);
                if ($byBillRefPhone) {
                    return $byBillRefPhone;
                }
            }
        }

        $msisdnPhone = $this->normalizeKenyanPhone($msisdn);

        if ($msisdnPhone) {
            return $this->outstandingInvoiceForPhone($ispId, $msisdnPhone);
        }

        return null;
    }

    protected function outstandingInvoiceForCustomer(int $ispId, int $customerId): ?Invoice
    {
        return Invoice::query()
            ->where('isp_id', $ispId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();
    }

    protected function outstandingInvoiceForPhone(int $ispId, string $normalizedPhone): ?Invoice
    {
        $local = substr($normalizedPhone, 3);
        $leadingZero = '0'.$local;

        $customer = Customer::query()
            ->where('isp_id', $ispId)
            ->where(function ($query) use ($normalizedPhone, $local, $leadingZero) {
                $query->where('phone_number', $normalizedPhone)
                    ->orWhere('phone_number', $local)
                    ->orWhere('phone_number', $leadingZero);
            })
            ->first();

        if (! $customer) {
            return null;
        }

        return $this->outstandingInvoiceForCustomer($ispId, (int) $customer->id);
    }

    protected function normalizeKenyanPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        $digits = preg_replace('/^0/', '254', $digits);

        if (preg_match('/^254[0-9]{9}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logUnreconciled(
        int $ispId,
        array $payload,
        ?string $ipAddress,
        string $reason,
        ?Invoice $partialMatch = null
    ): void {
        Log::warning('M-Pesa C2B unreconciled transaction', [
            'isp_id' => $ispId,
            'reason' => $reason,
            'bill_ref' => $payload['BillRefNumber'] ?? null,
            'trans_id' => $payload['TransID'] ?? null,
        ]);

        PaymentTransactionLog::log([
            'isp_id' => $ispId,
            'transaction_type' => 'c2b_unreconciled',
            'payment_method' => 'mpesa',
            'transaction_id' => isset($payload['TransID']) ? (string) $payload['TransID'] : null,
            'status' => 'unreconciled',
            'amount' => isset($payload['TransAmount']) ? (float) $payload['TransAmount'] : null,
            'request_data' => array_merge($payload, array_filter([
                'partial_match_invoice_id' => $partialMatch?->id,
                'partial_match_invoice_number' => $partialMatch?->invoice_number,
            ])),
            'ip_address' => $ipAddress,
            'error_message' => $reason,
        ]);
    }
}
