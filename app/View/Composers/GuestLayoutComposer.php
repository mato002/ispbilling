<?php

namespace App\View\Composers;

use App\Services\AuthPanelStatsService;
use Illuminate\View\View;

class GuestLayoutComposer
{
    public function __construct(
        protected AuthPanelStatsService $stats
    ) {}

    public function compose(View $view): void
    {
        $ispId = auth()->user()?->isp_id;

        $view->with('authPanelStats', $this->stats->build($ispId));
    }
}
