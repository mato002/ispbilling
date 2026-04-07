<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    public function index()
    {
        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)->latest()->get();
        return response()->json($routers);
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'username' => 'required|string',
            'password' => 'required|string',
            'port' => 'nullable|integer|min:1|max:65535',
            'use_ssl' => 'boolean',
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $router = Router::create(array_merge($validated, [
            'isp_id' => $ispId,
            'port' => $validated['port'] ?? 8728,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Router added successfully',
            'router' => $router,
        ], 201);
    }

    public function show(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($router);
    }

    public function update(Request $request, Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ip',
            'username' => 'sometimes|string',
            'password' => 'sometimes|string',
            'port' => 'nullable|integer|min:1|max:65535',
            'use_ssl' => 'boolean',
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $router->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Router updated successfully',
            'router' => $router,
        ]);
    }

    public function destroy(Router $router)
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($router->customers()->exists()) {
            return response()->json([
                'error' => 'Cannot delete router with assigned customers',
            ], 400);
        }

        $router->delete();

        return response()->json([
            'success' => true,
            'message' => 'Router deleted successfully',
        ]);
    }
}
