<?php

namespace App\View\Composers;

use App\Models\SystemAnnouncement;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class IspSystemAnnouncementsComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin() || ! $user->isp_id) {
            $view->with('systemAnnouncements', collect());

            return;
        }

        if (! request()->routeIs('isp.*')) {
            $view->with('systemAnnouncements', collect());

            return;
        }

        $view->with(
            'systemAnnouncements',
            Cache::remember('isp.system_announcements.visible', 120, static function () {
                return SystemAnnouncement::query()->visibleNow()->get();
            })
        );
    }
}
