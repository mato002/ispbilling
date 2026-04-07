<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $ispId = auth()->user()->isp_id;
        $plans = Plan::where('isp_id', $ispId)->latest()->get();
        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'speed_profile' => 'nullable|string',
            'upload_speed_kbps' => 'nullable|integer',
            'download_speed_kbps' => 'nullable|integer',
            'billing_type' => 'required|in:time,data,unlimited',
            'data_limit_mb' => 'nullable|integer|required_if:billing_type,data',
            'time_limit_hours' => 'nullable|integer|required_if:billing_type,time',
            'is_active' => 'boolean',
        ]);

        $plan = Plan::create(array_merge($validated, ['isp_id' => $ispId]));

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'plan' => $plan,
        ], 201);
    }

    public function show(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($plan);
    }

    public function update(Request $request, Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'sometimes|in:hotspot,pppoe',
            'price' => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'speed_profile' => 'nullable|string',
            'upload_speed_kbps' => 'nullable|integer',
            'download_speed_kbps' => 'nullable|integer',
            'billing_type' => 'sometimes|in:time,data,unlimited',
            'data_limit_mb' => 'nullable|integer',
            'time_limit_hours' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'plan' => $plan,
        ]);
    }

    public function destroy(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($plan->subscriptions()->exists()) {
            return response()->json([
                'error' => 'Cannot delete plan with active subscriptions',
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully',
        ]);
    }
}
