<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\IspPaymentExperience;
use Illuminate\Support\Facades\DB;

class PaymentPostingService
{
    public function __construct(
        protected PaymentCompletionService $paymentCompletionService
    ) {}

    /**
     * Manually assign an unposted payment to a customer (and optional invoice).
     */
    public function assignToCustomer(Payment $payment, Customer $customer, ?Invoice $invoice, User $actor): Payment
    {
        if ($payment->posting_status !== 'unposted') {
            throw new \InvalidArgumentException('Only unposted payments can be assigned.');
        }

        if ($payment->isp_id !== $customer->isp_id) {
            throw new \InvalidArgumentException('Customer belongs to a different workspace.');
        }

        if ($invoice && ($invoice->isp_id !== $payment->isp_id || $invoice->customer_id !== $customer->id)) {
            throw new \InvalidArgumentException('Invoice does not belong to this customer.');
        }

        DB::transaction(function () use ($payment, $customer, $invoice, $actor) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $amount = (float) $locked->amount;

            if ($invoice) {
                $invoiceTotal = (float) $invoice->total_amount;
                $applyAmount = min($amount, $invoiceTotal);

                $locked->update([
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'posting_status' => 'posted',
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'manual_assignment' => true,
                        'assigned_by_user_id' => $actor->id,
                    ]),
                ]);

                if ($locked->status !== 'completed') {
                    $locked->markAsCompleted();
                } elseif ($invoice->status !== 'paid') {
                    $invoice->markAsPaid();
                }

                $credit = round($amount - $applyAmount, 2);
                if ($credit > 0.01) {
                    $customer->increment('account_credit', $credit);
                    $locked->update([
                        'metadata' => array_merge($locked->fresh()->metadata ?? [], [
                            'credit_applied' => $credit,
                        ]),
                    ]);
                }
            } else {
                $locked->update([
                    'customer_id' => $customer->id,
                    'posting_status' => 'posted',
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'manual_assignment' => true,
                        'assigned_as_credit' => true,
                    ]),
                ]);

                if ($locked->status !== 'completed') {
                    $locked->markAsCompleted();
                }

                $customer->increment('account_credit', $amount);
            }
        });

        $fresh = $payment->fresh(['customer', 'invoice']);

        if ($fresh->customer && IspPaymentExperience::autoActivateOnPayment($fresh->isp)) {
            $this->paymentCompletionService->processTenantPaymentSuccess($fresh, true);
        }

        return $fresh;
    }
}
