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
            $preference = data_get(auth()->user()->preferences, 'notifs', 'all');
            $snapshot = $preference === 'none'
                ? ['notifications' => collect(), 'unread_count' => 0]
                : $this->notificationService->getDropdownSnapshot($ispId, 5, $preference === 'critical');

            View::share('recentNotifications', $snapshot['notifications']);
            View::share('unreadCount', $snapshot['unread_count']);
        }

        return $next($request);
    }
}
