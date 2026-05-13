<?php

namespace App\Support;

use App\Models\ISP;
use App\Models\PlatformIntegration;

class InvoiceTax
{
    /**
     * @return array{amount: float, tax: float, total_amount: float, rate_percent: float, label: string}
     */
    public static function computeForAmount(float $amount, ISP $isp): array
    {
        $platform = PlatformIntegration::current();
        $gb = $platform->global_billing ?? [];
        $enforce = (bool) ($gb['enforce_global_tax'] ?? false);
        $platformRate = (float) ($gb['global_tax_rate_percent'] ?? 0);
        $label = (string) ($gb['global_tax_label'] ?? 'VAT');

        $ispBilling = $isp->settings['billing'] ?? [];
        $ispRate = (float) ($ispBilling['tax_rate'] ?? 0);

        $rate = $enforce ? $platformRate : $ispRate;

        $tax = round($amount * ($rate / 100), 2);

        return [
            'amount' => $amount,
            'tax' => $tax,
            'total_amount' => round($amount + $tax, 2),
            'rate_percent' => $rate,
            'label' => $label,
        ];
    }

    public static function isGlobalTaxEnforced(): bool
    {
        $gb = PlatformIntegration::current()->global_billing ?? [];

        return (bool) ($gb['enforce_global_tax'] ?? false);
    }

    public static function enforcedTaxRatePercent(): float
    {
        $gb = PlatformIntegration::current()->global_billing ?? [];

        return (float) ($gb['global_tax_rate_percent'] ?? 0);
    }
}
