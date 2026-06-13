<?php

namespace Tests\Unit;

use App\Models\ISP;
use App\Support\IspNotificationPreferences;
use Tests\TestCase;

class IspNotificationPreferencesTest extends TestCase
{
    public function test_defaults_when_notifications_settings_missing(): void
    {
        $isp = new ISP(['settings' => []]);
        $isp->id = 1;

        $preferences = IspNotificationPreferences::forIsp($isp);

        $this->assertFalse($preferences->remindersEnabled());
        $this->assertSame(3, $preferences->reminderDays);
    }

    public function test_reads_tenant_invoice_reminder_preferences(): void
    {
        $isp = new ISP([
            'settings' => [
                'notifications' => [
                    'invoice_reminders' => true,
                    'reminder_days' => 7,
                ],
            ],
        ]);
        $isp->id = 2;

        $preferences = IspNotificationPreferences::forIsp($isp);

        $this->assertTrue($preferences->remindersEnabled());
        $this->assertSame(7, $preferences->reminderDays);
        $this->assertSame(
            now()->addDays(7)->toDateString(),
            $preferences->reminderDueBefore()->toDateString()
        );
    }

    public function test_clamps_negative_reminder_days_to_zero(): void
    {
        $isp = new ISP([
            'settings' => [
                'notifications' => [
                    'invoice_reminders' => true,
                    'reminder_days' => -5,
                ],
            ],
        ]);

        $preferences = IspNotificationPreferences::forIsp($isp);

        $this->assertSame(0, $preferences->reminderDays);
        $this->assertSame(now()->toDateString(), $preferences->reminderDueBefore()->toDateString());
    }
}
