<?php

namespace App\Support;

use App\Models\ISP;

/**
 * Tenant notification preferences stored under settings.notifications.
 */
class IspNotificationPreferences
{
    public function __construct(
        public readonly bool $invoiceRemindersEnabled,
        public readonly int $reminderDays,
    ) {}

    public static function forIsp(?ISP $isp): self
    {
        $notifications = is_array($isp?->settings)
            ? ($isp->settings['notifications'] ?? [])
            : [];

        return new self(
            invoiceRemindersEnabled: ! empty($notifications['invoice_reminders']),
            reminderDays: max(0, (int) ($notifications['reminder_days'] ?? 3)),
        );
    }

    public function remindersEnabled(): bool
    {
        return $this->invoiceRemindersEnabled;
    }

    /**
     * Inclusive upper bound for invoice due dates eligible for a reminder run.
     */
    public function reminderDueBefore(): \Illuminate\Support\Carbon
    {
        return now()->addDays($this->reminderDays)->endOfDay();
    }
}
