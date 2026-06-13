<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareNotifications
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only share notifications for authenticated ISP users
        if (auth()->check() && auth()->user()->isp_id) {
            $ispId = (int) auth()->user()->isp_id;
            $snapshot = $this->notificationService->getDropdownSnapshot($ispId);

            View::share('recentNotifications', $snapshot['notifications']);
            View::share('unreadCount', $snapshot['unread_count']);
        }

        return $next($request);
    }
}
