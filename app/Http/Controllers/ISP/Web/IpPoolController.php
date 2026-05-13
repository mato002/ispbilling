<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\IpPool;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpPoolController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $ipPools = IpPool::forIsp($ispId)
            ->with('router')
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('name')
            ->paginate(10)
            ->appends($request->query());

        $routers = Router::forIsp($ispId)->active()->get();

        return view('isp.ip-pools.index', compact('ipPools', 'routers'));
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $routers = Router::forIsp($ispId)->active()->get();

        return view('isp.ip-pools.create', compact('routers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'name' => 'required|string|max:255',
            'ranges' => 'required|array|min:1',
            'ranges.*.start' => 'required|ip',
            'ranges.*.end' => 'required|ip|after:ranges.*.start',
            'gateway_address' => 'nullable|ip',
            'netmask' => 'nullable|ip',
            'lease_time' => 'nullable|integer|min:60',
            'comment' => 'nullable|string|max:500',
        ]);

        $router = Router::findOrFail($request->router_id);

        // Verify router belongs to ISP
        if ($router->isp_id !== auth()->user()->isp_id) {
            return back()->with('error', 'Router not found');
        }

        try {
            // Create IP pool in database
            $ipPool = IpPool::create([
                'isp_id' => auth()->user()->isp_id,
                'router_id' => $router->id,
                'name' => $request->name,
                'ranges' => $request->ranges,
                'gateway_address' => $request->gateway_address,
                'netmask' => $request->netmask,
                'lease_time' => $request->lease_time ?? 300,
                'comment' => $request->comment,
                'is_active' => true,
            ]);

            // Create IP pool on router
            $mikrotikService = new MikroTikService($router);
            $result = $mikrotikService->createIpPool([
                'name' => $request->name,
                'ranges' => $request->ranges,
                'comment' => $request->comment,
            ]);

            if ($result) {
                return redirect()->route('isp.ip-pools.index')->with('success', 'IP pool created successfully');
            } else {
                // Pool created in database but failed on router
                return redirect()->route('isp.ip-pools.index')->with('warning', 'IP pool created but failed to sync with router');
            }

        } catch (\Exception $e) {
            Log::error('IP pool creation error: '.$e->getMessage());

            return back()->with('error', 'Failed to create IP pool: '.$e->getMessage())->withInput();
        }
    }

    public function sync(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
        ]);

        $router = Router::findOrFail($request->router_id);

        // Verify router belongs to ISP
        if ($router->isp_id !== auth()->user()->isp_id) {
            return back()->with('error', 'Router not found');
        }

        try {
            $mikrotikService = new MikroTikService($router);
            $result = $mikrotikService->syncIpPools();

            if ($result['success']) {
                $message = "Successfully synced {$result['total']} IP pools. ";
                $message .= "Created: {$result['created']}, Updated: {$result['updated']}";

                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Failed to sync IP pools: '.$result['message']);
            }

        } catch (\Exception $e) {
            Log::error('IP pool sync error: '.$e->getMessage());

            return back()->with('error', 'Failed to sync IP pools: '.$e->getMessage());
        }
    }

    public function show(IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $ipPool->load('router');

        return view('isp.ip-pools.show', compact('ipPool'));
    }

    public function edit(IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $ispId = auth()->user()->isp_id;
        $routers = Router::forIsp($ispId)->active()->get();
        $ipPool->load('router');

        return view('isp.ip-pools.edit', compact('ipPool', 'routers'));
    }

    public function update(Request $request, IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'ranges' => 'required|array|min:1',
            'ranges.*.start' => 'required|ip',
            'ranges.*.end' => 'required|ip|after:ranges.*.start',
            'gateway_address' => 'nullable|ip',
            'netmask' => 'nullable|ip',
            'lease_time' => 'nullable|integer|min:60',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            $ipPool->update([
                'name' => $request->name,
                'ranges' => $request->ranges,
                'gateway_address' => $request->gateway_address,
                'netmask' => $request->netmask,
                'lease_time' => $request->lease_time,
                'comment' => $request->comment,
            ]);

            return redirect()->route('isp.ip-pools.index')->with('success', 'IP pool updated successfully');

        } catch (\Exception $e) {
            Log::error('IP pool update error: '.$e->getMessage());

            return back()->with('error', 'Failed to update IP pool: '.$e->getMessage())->withInput();
        }
    }

    public function activate(IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $ipPool->update(['is_active' => true]);

        return back()->with('success', 'IP pool activated successfully');
    }

    public function deactivate(IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $ipPool->update(['is_active' => false]);

        return back()->with('success', 'IP pool deactivated successfully');
    }

    public function destroy(IpPool $ipPool)
    {
        if ($ipPool->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        // Check if pool is being used by customers
        if ($ipPool->customers()->count() > 0) {
            return back()->with('error', 'Cannot delete IP pool. It is being used by customers.');
        }

        $ipPool->delete();

        return redirect()->route('isp.ip-pools.index')->with('success', 'IP pool deleted successfully');
    }

    /**
     * Get available IP pools for selection
     */
    public function getAvailable(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $pools = IpPool::forIsp($ispId)
            ->active()
            ->with('router')
            ->get()
            ->map(function ($pool) {
                return [
                    'id' => $pool->id,
                    'name' => $pool->name,
                    'formatted_range' => $pool->formatted_range,
                    'total_ips' => $pool->total_ips,
                    'available_ips' => $pool->available_ips,
                    'router_name' => $pool->router->name,
                    'display_name' => "{$pool->name} ({$pool->formatted_range}) - {$pool->router->name}",
                ];
            });

        return response()->json($pools);
    }
}
