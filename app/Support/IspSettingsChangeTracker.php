<?php

namespace App\Support;

use App\Models\ISP;

class IspSettingsChangeTracker
{
    public static function touch(?int $ispId): void
    {
        if (! $ispId) {
            return;
        }

        $isp = ISP::query()->find($ispId);
        if (! $isp) {
            return;
        }

        $settings = $isp->settings ?? [];
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);

        $isp->update(['settings' => $settings]);
    }
}
