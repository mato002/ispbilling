<?php

namespace App\Http\Controllers\ISP\Web;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display notifications for the logged-in ISP
     */
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can access notifications');
        }

        $query = Notification::forIsp($ispId);

        // Filter by read status
        if ($request->has('status') && $request->status === 'unread') {
            $query->unread();
        } elseif ($request->has('status') && $request->status === 'read') {
            $query->read();
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $startDate = Carbon::parse($request->date_from)->startOfDay();
            $endDate = $request->has('date_to') && $request->date_to
                ? Carbon::parse($request->date_to)->endOfDay()
                : Carbon::now()->endOfDay();
            $query->dateRange($startDate, $endDate);
        }

        $notifications = $query->latest()->paginate(20)->appends($request->query());

        // Get counts for filters
        $unreadCount = Notification::forIsp($ispId)->unread()->count();
        $totalCount = Notification::forIsp($ispId)->count();

        return view('isp.notifications.index', compact('notifications', 'unreadCount', 'totalCount'));
    }

    /**
     * Get recent notifications for dropdown
     */
    public function recent(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $snapshot = $this->notificationService->getDropdownSnapshot(
            $ispId,
            NotificationService::DROPDOWN_LIMIT
        );

        return response()->json([
            'notifications' => $snapshot['notifications']->map(
                fn (Notification $notification) => $this->notificationService->formatForDropdown($notification)
            )->values(),
            'unread_count' => $snapshot['unread_count'],
        ]);
    }

    /**
     * Create a new notification
     */
    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:success,warning,info,error,payment,subscription,system,hotspot',
        ]);

        $notification = $this->notificationService->createNotification(
            $ispId,
            $request->title,
            $request->message,
            $request->type
        );

        // Broadcast the new notification
        broadcast(new NewNotification($notification));

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        $ispId = auth()->user()->isp_id;

        if ($notification->isp_id !== $ispId) {
            abort(403, 'Unauthorized access');
        }

        $notification->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted',
            ]);
        }

        return redirect()->route('isp.notifications.index')
            ->with('success', 'Notification deleted');
    }

    /**
     * Get notification statistics
     */
    public function stats(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->notificationService->getNotificationStats($ispId);

        return response()->json($stats);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $ispId = auth()->user()->isp_id;

        if ($notification->isp_id !== $ispId) {
            abort(403, 'Unauthorized access');
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can access notifications');
        }

        Notification::forIsp($ispId)->unread()->update(['is_read' => true]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        }

        return redirect()->route('isp.notifications.index')
            ->with('success', 'All notifications marked as read');
    }
}
