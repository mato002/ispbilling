<?php

namespace App\View\Composers;

use App\Models\SystemAnnouncement;
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
            SystemAnnouncement::query()->visibleNow()->get()
        );
    }
}
