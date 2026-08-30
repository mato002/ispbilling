<?php

namespace App\Http\Controllers\ISP\Web;

use App\Events\NewNotification;
use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\Export\CsvExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    use InteractsWithListings;

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

        $query = $this->filteredNotificationsQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $notifications = $query->latest()->paginate($perPage)->withQueryString();

        $unreadCount = Notification::forIsp($ispId)->unread()->count();
        $totalCount = Notification::forIsp($ispId)->count();

        $filterChips = $this->listingFilterChips($request, route('isp.notifications.index'), [
            'status' => [
                'label' => 'Status',
                'values' => ['unread' => 'Unread', 'read' => 'Read'],
            ],
            'type' => [
                'label' => 'Type',
                'values' => [
                    'payment' => 'Payment',
                    'subscription' => 'Subscription',
                    'system' => 'System',
                    'hotspot' => 'Hotspot',
                    'success' => 'Success',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'info' => 'Info',
                ],
            ],
            'date_from' => 'From',
            'date_to' => 'To',
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.notifications.export.csv'));

        return view('isp.notifications.index', compact(
            'notifications',
            'unreadCount',
            'totalCount',
            'filterChips',
            'exportFormats'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can access notifications');
        }

        $query = $this->filteredNotificationsQuery($request, $ispId)->latest();
        $filename = 'notifications-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Title', 'Message', 'Type', 'Read', 'Created',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($notifications) use ($out) {
                foreach ($notifications as $notification) {
                    fputcsv($out, [
                        $notification->title,
                        $notification->message,
                        $notification->type,
                        $notification->is_read ? 'Yes' : 'No',
                        $notification->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredNotificationsQuery(Request $request, int $ispId): Builder
    {
        $query = Notification::forIsp($ispId);

        if ($request->filled('status') && $request->status === 'unread') {
            $query->unread();
        } elseif ($request->filled('status') && $request->status === 'read') {
            $query->read();
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('date_from')) {
            $startDate = Carbon::parse($request->date_from)->startOfDay();
            $endDate = $request->filled('date_to')
                ? Carbon::parse($request->date_to)->endOfDay()
                : Carbon::now()->endOfDay();
            $query->dateRange($startDate, $endDate);
        }

        return $query;
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
