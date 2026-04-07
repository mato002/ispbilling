<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $query = Plan::where('isp_id', $ispId)->withCount('subscriptions');

        if ($request->has('access_type') && $request->access_type) {
            $query->where('access_type', $request->access_type);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $plans = $query->latest()->paginate(20);

        return view('isp.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('isp.plans.create');
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
            'upload_speed_kbps' => 'nullable|integer|min:0',
            'download_speed_kbps' => 'nullable|integer|min:0',
            'billing_type' => 'required|in:time,data,unlimited',
            'data_limit_mb' => 'nullable|integer|min:0|required_if:billing_type,data',
            'time_limit_hours' => 'nullable|integer|min:0|required_if:billing_type,time',
            'device_limit' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['isp_id'] = $ispId;
        $validated['is_active'] = $request->has('is_active');

        Plan::create($validated);

        return redirect()->route('isp.plans.index')->with('success', 'Plan created successfully');
    }

    public function show(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $plan->loadCount('subscriptions');
        return view('isp.plans.show', compact('plan'));
    }

    public function edit(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        return view('isp.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'speed_profile' => 'nullable|string',
            'upload_speed_kbps' => 'nullable|integer|min:0',
            'download_speed_kbps' => 'nullable|integer|min:0',
            'billing_type' => 'required|in:time,data,unlimited',
            'data_limit_mb' => 'nullable|integer|min:0|required_if:billing_type,data',
            'time_limit_hours' => 'nullable|integer|min:0|required_if:billing_type,time',
            'device_limit' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $plan->update($validated);

        return redirect()->route('isp.plans.index')->with('success', 'Plan updated successfully');
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => !$plan->is_active]);

        $message = $plan->is_active 
            ? 'Plan activated successfully.' 
            : 'Plan deactivated successfully.';

        return back()->with('success', $message);
    }

    /**
     * Activate plan
     */
    public function activate(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => true]);

        return back()->with('success', 'Plan activated successfully.');
    }

    /**
     * Deactivate plan
     */
    public function deactivate(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => false]);

        return back()->with('success', 'Plan deactivated successfully.');
    }
}

