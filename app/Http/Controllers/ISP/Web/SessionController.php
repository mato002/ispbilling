<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\ISP\BaseController;
use App\Models\HotspotSession;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SessionController extends BaseController
{
    use InteractsWithListings;

    /**
     * Display active sessions
     */
    public function index(Request $request)
    {
        $ispId = $this->getIspId();

        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $routerLabels = $routers->pluck('name', 'id')->mapWithKeys(
            fn (string $name, int $id): array => [(string) $id => $name]
        )->all();

        $query = $this->filteredSessionsQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request, 25);
        $sessions = $query->paginate($perPage)->withQueryString();

        $stats = [
            'active_sessions' => HotspotSession::where('isp_id', $ispId)->where('status', 'active')->count(),
            'total_today' => HotspotSession::where('isp_id', $ispId)->whereDate('started_at', today())->count(),
            'total_bandwidth_today' => HotspotSession::where('isp_id', $ispId)
                ->whereDate('started_at', today())
                ->sum(\DB::raw('bytes_uploaded + bytes_downloaded')),
        ];

        $filterChips = $this->listingFilterChips($request, route('isp.sessions.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'terminated' => 'Terminated',
                    'expired' => 'Expired',
                ],
            ],
            'router_id' => [
                'label' => 'Router',
                'values' => $routerLabels,
            ],
            'date_from' => 'From',
            'date_to' => 'To',
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.sessions.export.csv'));

        return view('isp.sessions.index', compact('sessions', 'routers', 'stats', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = $this->getIspId();
        $query = $this->filteredSessionsQuery($request, $ispId)->with(['router', 'customer']);
        $filename = 'sessions-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Username', 'Customer', 'Phone', 'IP', 'MAC', 'Router', 'Status',
            'Started', 'Ended', 'Upload (bytes)', 'Download (bytes)',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($sessions) use ($out) {
                foreach ($sessions as $session) {
                    fputcsv($out, [
                        $session->username,
                        $session->customer?->full_name,
                        $session->customer?->phone_number,
                        $session->ip_address,
                        $session->mac_address,
                        $session->router?->name,
                        $session->status,
                        $session->started_at?->format('Y-m-d H:i:s'),
                        $session->ended_at?->format('Y-m-d H:i:s'),
                        $session->bytes_uploaded,
                        $session->bytes_downloaded,
                    ]);
                }
            });
        });
    }

    protected function filteredSessionsQuery(Request $request, int $ispId): Builder
    {
        $query = HotspotSession::where('isp_id', $ispId)
            ->with(['router', 'customer', 'customer.activeSubscription.plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('username', 'like', $pattern)
                    ->orWhere('ip_address', 'like', $pattern)
                    ->orWhere('mac_address', 'like', $pattern)
                    ->orWhereHas('customer', function ($q2) use ($pattern) {
                        $q2->where('full_name', 'like', $pattern)
                            ->orWhere('phone_number', 'like', $pattern);
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('started_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('started_at', '<=', $request->date_to);
        }

        return $query->latest('started_at');
    }

    /**
     * Show session details
     */
    public function show(HotspotSession $session)
    {
        $this->ensureIspAccess($session);

        $session->load(['router', 'customer', 'customer.activeSubscription.plan', 'isp']);

        // Get real-time stats from MikroTik if active
        $realTimeStats = null;
        if ($session->status === 'active' && $session->router) {
            try {
                $mikrotik = new MikroTikService($session->router);
                if ($mikrotik->connect()) {
                    $connection = $mikrotik->getConnection();
                    $activeSessions = $connection->comm('/ip/hotspot/active/print', [
                        '?user' => $session->username,
                    ]);

                    if (! empty($activeSessions)) {
                        $realTimeStats = $activeSessions[0];
                    }
                }
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        return view('isp.sessions.show', compact('session', 'realTimeStats'));
    }

    /**
     * Force disconnect a session
     */
    public function disconnect(HotspotSession $session)
    {
        $this->ensureIspAccess($session);

        if ($session->status !== 'active') {
            return back()->with('error', 'Session is not active.');
        }

        try {
            $mikrotik = new MikroTikService($session->router);
            if ($mikrotik->connect()) {
                $customer = $session->customer ?? new \App\Models\Customer([
                    'username' => $session->username,
                    'connection_type' => 'hotspot',
                ]);
                $mikrotik->disconnectSession($customer);
            }

            $session->update([
                'status' => 'terminated',
                'ended_at' => now(),
                'termination_reason' => 'Disconnected by administrator',
            ]);

            return back()->with('success', 'Session disconnected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to disconnect session: '.$e->getMessage());
        }
    }

    /**
     * Get session history for a customer
     */
    public function customerHistory(Request $request, $customerId)
    {
        $ispId = $this->getIspId();

        $customer = \App\Models\Customer::where('id', $customerId)
            ->where('isp_id', $ispId)
            ->firstOrFail();

        $sessions = HotspotSession::where('customer_id', $customerId)
            ->with(['router'])
            ->latest('started_at')
            ->paginate(20);

        return view('isp.sessions.customer-history', compact('customer', 'sessions'));
    }
}
