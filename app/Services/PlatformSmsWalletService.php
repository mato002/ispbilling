<?php

namespace App\Services;

use App\Models\ISP;
use App\Models\Payment;
use App\Models\SmsPlatformLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformSmsWalletService
{
    public const PURPOSE = 'sms_wallet_topup';

    /**
     * Initiate platform M-Pesa STK to credit an ISP SMS wallet.
     *
     * @return array{success: bool, message: string, payment?: Payment}
     */
    public function initiateTopUp(ISP $isp, string $phone254, float $amount): array
    {
        $amount = round($amount, 2);
        if ($amount < 10) {
            return ['success' => false, 'message' => 'Minimum top-up amount is 10.'];
        }

        $payment = Payment::create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_method' => 'mpesa',
            'amount' => $amount,
            'phone_number' => $phone254,
            'status' => 'pending',
            'metadata' => [
                'purpose' => self::PURPOSE,
                'sms_wallet_topup' => true,
            ],
        ]);

        $mpesa = new MpesaService(null);
        $result = $mpesa->initiateStkPush($payment);

        if (! ($result['success'] ?? false)) {
            $payment->markAsFailed();

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Could not start M-Pesa STK push.',
                'payment' => $payment->fresh(),
            ];
        }

        return [
            'success' => true,
            'message' => 'M-Pesa STK push sent. Approve the prompt to credit your SMS wallet.',
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Credit ISP SMS wallet after a successful platform M-Pesa payment (idempotent).
     */
    public function creditFromPayment(Payment $payment): void
    {
        if (! $payment->isSmsWalletTopUp() || ! $payment->isp_id) {
            return;
        }

        if (! empty($payment->metadata['sms_wallet_credited_at'])) {
            return;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return;
        }

        $rate = (float) config('sms.unit_cost', 0.60);
        $smsCount = $rate > 0 ? (int) floor($amount / $rate) : 0;

        DB::transaction(function () use ($payment, $amount, $smsCount) {
            $locked = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $locked || ! empty($locked->metadata['sms_wallet_credited_at'])) {
                return;
            }

            $isp = ISP::query()->lockForUpdate()->find($locked->isp_id);
            if (! $isp) {
                return;
            }

            $isp->increment('sms_balance', $amount);

            SmsPlatformLedger::create([
                'tenant_id' => $isp->id,
                'amount' => $amount,
                'type' => SmsPlatformLedger::TYPE_TOPUP,
                'sms_count' => $smsCount,
                'description' => 'M-Pesa SMS wallet top-up ('.$locked->payment_reference.')',
            ]);

            $locked->update([
                'metadata' => array_merge($locked->metadata ?? [], [
                    'sms_wallet_credited_at' => now()->toIso8601String(),
                    'sms_credits_added' => $smsCount,
                ]),
            ]);

            Log::info('SMS wallet credited from payment', [
                'payment_id' => $locked->id,
                'isp_id' => $isp->id,
                'amount' => $amount,
                'sms_count' => $smsCount,
            ]);
        });
    }
}
