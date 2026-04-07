<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\HotspotSession;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;

class SessionController extends BaseController
{
    /**
     * Display active sessions
     */
    public function index(Request $request)
    {
        $ispId = $this->getIspId();
        
        $query = HotspotSession::where('isp_id', $ispId)
            ->with(['router', 'customer', 'customer.activeSubscription.plan']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        // Filter by router
        if ($request->has('router_id') && $request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        // Date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('started_at', '<=', $request->date_to);
        }

        $sessions = $query->latest('started_at')->paginate(50);
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();

        // Get real-time stats
        $stats = [
            'active_sessions' => HotspotSession::where('isp_id', $ispId)->where('status', 'active')->count(),
            'total_today' => HotspotSession::where('isp_id', $ispId)->whereDate('started_at', today())->count(),
            'total_bandwidth_today' => HotspotSession::where('isp_id', $ispId)
                ->whereDate('started_at', today())
                ->sum(\DB::raw('bytes_uploaded + bytes_downloaded')),
        ];

        return view('isp.sessions.index', compact('sessions', 'routers', 'stats'));
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

                    if (!empty($activeSessions)) {
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
            return back()->with('error', 'Failed to disconnect session: ' . $e->getMessage());
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





