<?php

namespace App\Support;

use App\Models\ISP;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * ISP-configurable payment UX (instructions, STK account reference, auto-service).
 */
class IspPaymentExperience
{
    public static function defaults(): array
    {
        return [
            'customer_instructions' => '',
            'stk_account_reference' => 'payment_ref',
            'auto_activate_on_payment' => true,
            'sms_sender_id' => '',
            'grace_period_days' => 0,
            'auto_reconnect_on_payment' => true,
            'auto_receipt_email' => true,
            'auto_receipt_sms' => false,
            'partial_payment_policy' => 'proportional',
            'manual_payments_enabled' => true,
            'offline_cash_enabled' => true,
            'offline_bank_enabled' => true,
            'credit_apply_next_cycle' => true,
        ];
    }

    public static function forIsp(?ISP $isp): array
    {
        if (! $isp) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $isp->settings['payment_experience'] ?? []);
    }

    public static function stkAccountReference(Payment $payment): string
    {
        $mode = self::forIsp($payment->isp)['stk_account_reference'] ?? 'payment_ref';
        $customer = $payment->customer;

        if (! $customer) {
            return Str::limit((string) $payment->payment_reference, 20, '');
        }

        $raw = match ($mode) {
            'phone' => preg_replace('/[^0-9]/', '', (string) ($customer->phone_number ?? '')),
            'full_name' => (string) ($customer->full_name ?? ''),
            'customer_id' => 'C'.$customer->id,
            default => (string) $payment->payment_reference,
        };

        $raw = trim(preg_replace('/\s+/', ' ', (string) $raw));

        return Str::limit($raw === '' ? (string) $payment->payment_reference : $raw, 20, '');
    }

    public static function autoActivateOnPayment(?ISP $isp): bool
    {
        return (bool) (self::forIsp($isp)['auto_activate_on_payment'] ?? true);
    }
}
