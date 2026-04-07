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
            $ispId = auth()->user()->isp_id;
            
            // Share recent notifications and unread count with all views
            View::share('recentNotifications', $this->notificationService->getRecentNotifications($ispId, 5));
            View::share('unreadCount', $this->notificationService->getUnreadCount($ispId));
        }

        return $next($request);
    }
}
