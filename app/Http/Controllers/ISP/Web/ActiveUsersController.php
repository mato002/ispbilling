<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\HotspotSession;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActiveUsersController extends Controller
{
    /**
     * Display active users monitoring dashboard
     */
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $planId = $request->integer('plan_id') ?: null;
        if ($planId) {
            abort_unless(\App\Models\Plan::where('isp_id', $ispId)->whereKey($planId)->exists(), 404);
        }
        
        // Get active sessions from database
        $activeSessions = HotspotSession::whereHas('customer', function ($query) use ($ispId) {
                $query->where('isp_id', $ispId);
            })
            ->with(['customer', 'router'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Get routers for filtering
        $routers = Router::forIsp($ispId)->active()->get();

        // Get statistics
        $stats = [
            'total_active_users' => $activeSessions->count(),
            'total_routers' => $routers->count(),
            'connected_routers' => $activeSessions->pluck('router_id')->unique()->count(),
            'average_duration' => $activeSessions->avg('duration') ?? 0,
        ];

        $selectedPlan = $planId ? \App\Models\Plan::findOrFail($planId) : null;
        return view('isp.active-users.index', compact('activeSessions', 'routers', 'stats', 'selectedPlan'));
    }

    /**
     * Get real-time active users from router
     */
    public function getRealTimeUsers(Request $request)
    {
        $request->validate([
            'router_id' => 'nullable|exists:routers,id'
        ]);

        $ispId = auth()->user()->isp_id;
        $routerId = $request->router_id;
        $planId = $request->integer('plan_id') ?: null;
        $usernamesForPlan = null;
        if ($planId) {
            $plan = \App\Models\Plan::where('isp_id', $ispId)->findOrFail($planId);
            $usernamesForPlan = Customer::where('isp_id', $ispId)
                ->whereHas('subscriptions', fn ($query) => $query->where('plan_id', $plan->id)->where('status', 'active')->where('end_date', '>=', now()->toDateString()))
                ->pluck('username')->filter()->map(fn ($username) => strtolower($username))->all();
        }

        try {
            if ($routerId) {
                // Get users from specific router
                $router = Router::where('isp_id', $ispId)->findOrFail($routerId);
                $users = $this->getActiveUsersFromRouter($router);
            } else {
                // Get users from all routers
                $routers = Router::forIsp($ispId)->active()->get();
                $users = [];
                
                foreach ($routers as $router) {
                    $routerUsers = $this->getActiveUsersFromRouter($router);
                    $users = array_merge($users, $routerUsers);
                }
            }

            if ($usernamesForPlan !== null) {
                $allowed = array_fill_keys($usernamesForPlan, true);
                $users = array_values(array_filter($users, fn ($user) => isset($allowed[strtolower((string) ($user['username'] ?? ''))])));
            }

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => count($users),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting real-time active users: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get active users from specific router
     */
    private function getActiveUsersFromRouter(Router $router): array
    {
        try {
            $mikrotikService = new MikroTikService($router);
            
            // Get hotspot active users
            $hotspotUsers = $mikrotikService->getHotspotActiveUsers();
            
            // Get PPPoE active users
            $pppoeUsers = $mikrotikService->getPPPoEActiveUsers();
            
            // Combine and format users
            $users = [];
            
            foreach ($hotspotUsers as $user) {
                $customer = Customer::where('isp_id', $router->isp_id)
                    ->where('router_id', $router->id)
                    ->where('username', $user['user'] ?? '')
                    ->with(['subscriptions' => fn ($query) => $query->where('status', 'active')->where('end_date', '>=', now())->with('plan')])
                    ->first();
                $activePlan = $customer?->subscriptions->sortByDesc('end_date')->first()?->plan;
                $users[] = [
                    'id' => $user['.id'] ?? $user['user'] ?? '',
                    'username' => $user['user'] ?? '',
                    'ip_address' => $user['address'] ?? '',
                    'mac_address' => $user['mac-address'] ?? '',
                    'device' => $this->detectDevice($user),
                    'duration' => $this->formatDuration($user['uptime'] ?? ''),
                    'login_time' => $user['login-time'] ?? '',
                    'router' => $router->name,
                    'router_id' => $router->id,
                    'connection_type' => 'Hotspot',
                    'speed_limit' => $activePlan?->formatted_speed ?? 'Unknown',
                    'upload_speed' => $user['bytes-out'] ?? 0,
                    'download_speed' => $user['bytes-in'] ?? 0,
                    'session_id' => $user['session-id'] ?? '',
                ];
            }
            
            foreach ($pppoeUsers as $user) {
                $customer = Customer::where('isp_id', $router->isp_id)
                    ->where('router_id', $router->id)
                    ->where('username', $user['name'] ?? '')
                    ->with(['subscriptions' => fn ($query) => $query->where('status', 'active')->where('end_date', '>=', now())->with('plan')])
                    ->first();
                $activePlan = $customer?->subscriptions->sortByDesc('end_date')->first()?->plan;
                $users[] = [
                    'id' => $user['.id'] ?? $user['name'] ?? '',
                    'username' => $user['name'] ?? '',
                    'ip_address' => $user['address'] ?? '',
                    'mac_address' => $user['caller-id'] ?? '',
                    'device' => $this->detectDevice($user),
                    'duration' => $this->formatDuration($user['uptime'] ?? ''),
                    'login_time' => $user['uptime'] ?? '',
                    'router' => $router->name,
                    'router_id' => $router->id,
                    'connection_type' => 'PPPoE',
                    'speed_limit' => $activePlan?->formatted_speed ?? 'Unknown',
                    'upload_speed' => $user['bytes-out'] ?? 0,
                    'download_speed' => $user['bytes-in'] ?? 0,
                    'session_id' => $user['.id'] ?? '',
                ];
            }
            
            return $users;

        } catch (\Exception $e) {
            Log::error("Error getting active users from router {$router->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detect device type from MAC address or user agent
     */
    private function detectDevice(array $user): string
    {
        // Try to detect from MAC address vendor
        $macAddress = $user['mac-address'] ?? $user['caller-id'] ?? '';
        
        if ($macAddress) {
            $macPrefix = strtoupper(substr($macAddress, 0, 8));
            
            // Common MAC address prefixes
            $vendors = [
                '00:1A:2B' => 'Android',
                '28:CF:E9' => 'Android',
                '34:80:B3' => 'Android',
                'AC:5A:FC' => 'iPhone',
                '40:A6:D9' => 'iPhone',
                '64:20:9F' => 'iPhone',
                '70:72:3C' => 'Android',
                'B8:27:EB' => 'Android',
                'F0:A4:5B' => 'Android',
                '00:16:CB' => 'Router',
                '00:0C:43' => 'Router',
                '00:1D:0F' => 'Router',
                '00:22:B0' => 'Router',
                '00:23:69' => 'Router',
                '00:24:01' => 'Router',
                '00:25:90' => 'Router',
                '08:60:6E' => 'Router',
                '08:96:1D' => 'Router',
                '14:CC:20' => 'Router',
                '18:C0:4D' => 'Router',
                '24:65:11' => 'Router',
                '28:C6:3F' => 'Router',
                '30:85:A9' => 'Router',
                '44:1E:A1' => 'Router',
                '50:C7:BF' => 'Router',
                '78:24:AF' => 'Router',
                'A0:C5:89' => 'Router',
                'B4:75:0E' => 'Router',
                'C0:4A:00' => 'Router',
                'D4:6E:0E' => 'Router',
                'E0:4F:43' => 'Router',
                'F8:E7:1E' => 'Router',
            ];
            
            foreach ($vendors as $prefix => $device) {
                if (strpos($macPrefix, $prefix) !== false) {
                    return $device;
                }
            }
        }
        
        // Try to detect from user agent if available
        $userAgent = $user['user-agent'] ?? '';
        if ($userAgent) {
            if (stripos($userAgent, 'android') !== false) {
                return 'Android';
            } elseif (stripos($userAgent, 'iphone') !== false) {
                return 'iPhone';
            } elseif (stripos($userAgent, 'ipad') !== false) {
                return 'iPad';
            } elseif (stripos($userAgent, 'windows') !== false) {
                return 'Windows';
            } elseif (stripos($userAgent, 'mac') !== false) {
                return 'macOS';
            } elseif (stripos($userAgent, 'linux') !== false) {
                return 'Linux';
            }
        }
        
        return 'Unknown';
    }

    /**
     * Format duration from MikroTik uptime format
     */
    private function formatDuration(string $uptime): string
    {
        if (empty($uptime)) {
            return '0 min';
        }

        // Parse MikroTik uptime format (e.g., "2w3d15h20m30s")
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        // Extract weeks
        if (preg_match('/(\d+)w/', $uptime, $matches)) {
            $weeks = (int)$matches[1];
        }

        // Extract days
        if (preg_match('/(\d+)d/', $uptime, $matches)) {
            $days = (int)$matches[1];
        }

        // Extract hours
        if (preg_match('/(\d+)h/', $uptime, $matches)) {
            $hours = (int)$matches[1];
        }

        // Extract minutes
        if (preg_match('/(\d+)m/', $uptime, $matches)) {
            $minutes = (int)$matches[1];
        }

        // Extract seconds
        if (preg_match('/(\d+)s/', $uptime, $matches)) {
            $seconds = (int)$matches[1];
        }

        // Convert to total minutes
        $totalMinutes = ($weeks * 7 * 24 * 60) + ($days * 24 * 60) + ($hours * 60) + $minutes + ($seconds / 60);

        // Format human readable
        if ($totalMinutes < 60) {
            return round($totalMinutes) . ' min';
        } elseif ($totalMinutes < 1440) {
            return round($totalMinutes / 60, 1) . ' hours';
        } elseif ($totalMinutes < 10080) {
            return round($totalMinutes / 1440, 1) . ' days';
        } else {
            return round($totalMinutes / 10080, 1) . ' weeks';
        }
    }

    /**
     * Disconnect a specific user
     */
    public function disconnectUser(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'user_id' => 'required|string',
            'connection_type' => 'required|in:hotspot,pppoe'
        ]);

        $ispId = auth()->user()->isp_id;
        
        try {
            $router = Router::where('isp_id', $ispId)->findOrFail($request->router_id);
            $mikrotikService = new MikroTikService($router);

            if ($request->connection_type === 'hotspot') {
                $result = $mikrotikService->disconnectHotspotUser($request->user_id);
            } else {
                $result = $mikrotikService->disconnectPPPoEUser($request->user_id);
            }

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'User disconnected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to disconnect user'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error disconnecting user: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        try {
            // Get real-time stats from all routers
            $routers = Router::forIsp($ispId)->active()->get();
            $totalUsers = 0;
            $totalUpload = 0;
            $totalDownload = 0;
            $routerStats = [];

            foreach ($routers as $router) {
                $users = $this->getActiveUsersFromRouter($router);
                $routerUpload = array_sum(array_column($users, 'upload_speed'));
                $routerDownload = array_sum(array_column($users, 'download_speed'));
                
                $routerStats[] = [
                    'router_name' => $router->name,
                    'active_users' => count($users),
                    'upload' => $routerUpload,
                    'download' => $routerDownload,
                ];
                
                $totalUsers += count($users);
                $totalUpload += $routerUpload;
                $totalDownload += $routerDownload;
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_active_users' => $totalUsers,
                    'total_upload' => $totalUpload,
                    'total_download' => $totalDownload,
                    'router_stats' => $routerStats,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting user stats: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
