<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $query = Router::where('isp_id', $ispId)->withCount('customers');

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $routers = $query->latest()->paginate(20);

        return view('isp.routers.index', compact('routers'));
    }

    public function create()
    {
        return view('isp.routers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $routerData = [
            'isp_id' => auth()->user()->isp_id,
            'name' => $request->name,
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'username' => $request->username,
            'password' => $request->password,
            'location' => $request->location,
            'use_ssl' => $request->has('use_ssl'),
            'is_active' => $request->has('is_active'),
        ];

        $router = Router::create($routerData);

        // Test connection and import hotspot servers if successful
        try {
            $mikrotik = new \App\Services\MikroTikService($router);
            $result = $mikrotik->testConnection();

            if ($result['success']) {
                // Import hotspot servers
                $importedCount = $this->importHotspotServers($router, $result['hotspot_servers']);
                
                if ($importedCount > 0) {
                    return redirect()->route('isp.routers.index')
                        ->with('success', "Router added successfully! Connection established and {$importedCount} hotspot servers imported automatically.");
                } else {
                    return redirect()->route('isp.routers.index')
                        ->with('success', 'Router added successfully! Connection established but no hotspot servers found.');
                }
            } else {
                // Router created but connection failed
                return redirect()->route('isp.routers.index')
                    ->with('warning', 'Router added but connection test failed: ' . $result['error'] . '. Please check router settings and try testing connection manually.');
            }
        } catch (\Exception $e) {
            // Router created but connection test failed
            return redirect()->route('isp.routers.index')
                ->with('warning', 'Router added but automatic connection test failed: ' . $e->getMessage() . '. Please test connection manually.');
        }

        return redirect()->route('isp.routers.index')->with('success', 'Router added successfully');
    }

    /**
     * Import hotspot servers from router
     */
    private function importHotspotServers(Router $router, array $hotspotServers): int
    {
        $importedCount = 0;

        foreach ($hotspotServers as $serverData) {
            try {
                \App\Models\HotspotServer::updateOrCreate(
                    [
                        'router_id' => $router->id,
                        'name' => $serverData['name'],
                    ],
                    [
                        'isp_id' => $router->isp_id,
                        'interface' => $serverData['interface'] ?? 'unknown',
                        'server_profile' => $serverData['server_profile'] ?? null,
                        'default_profile' => $serverData['default_profile'] ?? null,
                        'login_by' => $serverData['login_by'] ?? 'cookie',
                        'use_radius' => $serverData['use_radius'] ?? false,
                        'radius_accounting' => $serverData['radius_accounting'] ?? false,
                        'mac_cookie_timeout' => $serverData['mac_cookie_timeout'] ?? 300,
                        'idle_timeout' => $serverData['idle_timeout'] ?? 300,
                        'keepalive_timeout' => $serverData['keepalive_timeout'] ?? 60,
                        'status_autologout' => $serverData['status_autologout'] ?? false,
                        'allowed_users' => $serverData['allowed_users'] ?? 0,
                        'shared_users' => $serverData['shared_users'] ?? 0,
                        'mac_format' => $serverData['mac_format'] ?? null,
                        'mac_password' => $serverData['mac_password'] ?? null,
                        'radius_mac_format' => $serverData['radius_mac_format'] ?? null,
                        'radius_mac_password' => $serverData['radius_mac_password'] ?? null,
                        'trial_uptime' => $serverData['trial_uptime'] ?? 0,
                        'trial_uptime_reset' => $serverData['trial_uptime_reset'] ?? 0,
                        'trial_profile' => $serverData['trial_profile'] ?? null,
                        'trial_users' => $serverData['trial_users'] ?? 0,
                        'disabled' => $serverData['disabled'] ?? false,
                        'running' => $serverData['running'] ?? false,
                        'is_active' => !($serverData['disabled'] ?? false),
                    ]
                );
                $importedCount++;
            } catch (\Exception $e) {
                \Log::error('Failed to import hotspot server: ' . $e->getMessage(), [
                    'router_id' => $router->id,
                    'server_name' => $serverData['name'] ?? 'unknown',
                ]);
            }
        }

        return $importedCount;
    }

    public function show(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $router->loadCount('customers');
        return view('isp.routers.show', compact('router'));
    }

    public function edit(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        return view('isp.routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $routerData = [
            'name' => $request->name,
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'username' => $request->username,
            'password' => $request->password,
            'location' => $request->location,
            'use_ssl' => $request->has('use_ssl'),
            'is_active' => $request->has('is_active'),
        ];

        $router->update($routerData);

        return redirect()->route('isp.routers.index')->with('success', 'Router updated successfully');
    }

    /**
     * Activate router
     */
    public function activate(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $router->update(['is_active' => true]);

        return back()->with('success', 'Router activated successfully.');
    }

    /**
     * Deactivate router
     */
    public function deactivate(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $router->update(['is_active' => false]);

        return back()->with('success', 'Router deactivated successfully.');
    }

    /**
     * Test router connection (for new router before saving)
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'use_ssl' => 'nullable|boolean',
        ]);

        // Create temporary router object for testing
        $tempRouter = new Router([
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'username' => $request->username,
            'password' => $request->password,
            'use_ssl' => $request->has('use_ssl'),
        ]);

        try {
            $mikrotik = new \App\Services\MikroTikService($tempRouter);
            $result = $mikrotik->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Router is online.',
                    'router_info' => $result['router_info'],
                    'hotspot_servers' => $result['hotspot_servers'],
                    'hotspot_servers_count' => $result['hotspot_servers_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test existing router connection
     */
    public function testExistingConnection(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($router);
            $result = $mikrotik->testConnection();

            if ($result['success']) {
                return back()->with('success', 'Router connection test successful! Found ' . $result['hotspot_servers_count'] . ' hotspot servers.');
            } else {
                return back()->with('error', 'Failed to connect to router: ' . $result['error']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }
}

