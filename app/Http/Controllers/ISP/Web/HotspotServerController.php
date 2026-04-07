<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\HotspotServer;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotspotServerController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $hotspotServers = HotspotServer::forIsp($ispId)
            ->with('router')
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('name')
            ->paginate(10);

        $routers = Router::forIsp($ispId)->active()->get();

        return view('isp.hotspot-servers.index', compact('hotspotServers', 'routers'));
    }

    public function sync(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id'
        ]);

        $router = Router::findOrFail($request->router_id);
        
        // Verify router belongs to ISP
        if ($router->isp_id !== auth()->user()->isp_id) {
            return back()->with('error', 'Router not found');
        }

        try {
            $mikrotikService = new MikroTikService($router);
            $result = $mikrotikService->syncHotspotServers();

            if ($result['success']) {
                $message = "Successfully synced {$result['total']} hotspot servers. ";
                $message .= "Created: {$result['created']}, Updated: {$result['updated']}";
                
                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Failed to sync hotspot servers: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Hotspot sync error: ' . $e->getMessage());
            return back()->with('error', 'Failed to sync hotspot servers: ' . $e->getMessage());
        }
    }

    public function activate(HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $hotspotServer->update(['is_active' => true]);

        return back()->with('success', 'Hotspot server activated successfully');
    }

    public function deactivate(HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $hotspotServer->update(['is_active' => false]);

        return back()->with('success', 'Hotspot server deactivated successfully');
    }

    public function show(HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $hotspotServer->load('router');

        return view('isp.hotspot-servers.show', compact('hotspotServer'));
    }

    public function edit(HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $hotspotServer->load('router');

        return view('isp.hotspot-servers.edit', compact('hotspotServer'));
    }

    public function update(Request $request, HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'default_profile' => 'nullable|string|max:255',
            'login_by' => 'required|in:cookie,http-chap,mac',
            'use_radius' => 'nullable|boolean',
            'radius_accounting' => 'nullable|boolean',
            'mac_cookie_timeout' => 'nullable|integer|min:0',
            'idle_timeout' => 'nullable|integer|min:0',
            'keepalive_timeout' => 'nullable|integer|min:0',
            'status_autologout' => 'nullable|boolean',
            'allowed_users' => 'nullable|integer|min:0',
            'shared_users' => 'nullable|integer|min:0',
            'trial_uptime' => 'nullable|integer|min:0',
            'trial_uptime_reset' => 'nullable|integer|min:0',
            'trial_profile' => 'nullable|string|max:255',
            'trial_users' => 'nullable|integer|min:0',
        ]);

        $updateData = [
            'default_profile' => $request->default_profile,
            'login_by' => $request->login_by,
            'use_radius' => $request->has('use_radius'),
            'radius_accounting' => $request->has('radius_accounting'),
            'mac_cookie_timeout' => $request->mac_cookie_timeout ?? 300,
            'idle_timeout' => $request->idle_timeout ?? 300,
            'keepalive_timeout' => $request->keepalive_timeout ?? 60,
            'status_autologout' => $request->has('status_autologout'),
            'allowed_users' => $request->allowed_users ?? 0,
            'shared_users' => $request->shared_users ?? 0,
            'trial_uptime' => $request->trial_uptime ?? 0,
            'trial_uptime_reset' => $request->trial_uptime_reset ?? 0,
            'trial_profile' => $request->trial_profile,
            'trial_users' => $request->trial_users ?? 0,
        ];

        $hotspotServer->update($updateData);

        return redirect()->route('isp.hotspot-servers.index')->with('success', 'Hotspot server updated successfully');
    }

    public function destroy(HotspotServer $hotspotServer)
    {
        if ($hotspotServer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        // Check if server is being used by customers
        if ($hotspotServer->customers()->count() > 0) {
            return back()->with('error', 'Cannot delete hotspot server. It is being used by customers.');
        }

        $hotspotServer->delete();

        return redirect()->route('isp.hotspot-servers.index')->with('success', 'Hotspot server deleted successfully');
    }

    /**
     * Get available hotspot servers for selection
     */
    public function getAvailable(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $servers = HotspotServer::forIsp($ispId)
            ->active()
            ->with('router')
            ->get()
            ->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'interface' => $server->interface,
                    'router_name' => $server->router->name,
                    'display_name' => "{$server->name} ({$server->interface}) - {$server->router->name}"
                ];
            });

        return response()->json($servers);
    }
}
