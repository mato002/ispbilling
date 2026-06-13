<?php

namespace App\Services\Sms\Concerns;

trait NormalizesSmsRecipients
{
    protected function normalizeRecipient(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return $phone;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (strlen($digits) === 9 && in_array($digits[0], ['7', '1'], true)) {
            return '254'.$digits;
        }

        return $digits;
    }
}
