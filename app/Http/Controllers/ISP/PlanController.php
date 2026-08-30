<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\PlanPackageTypes;
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

        $validated = $request->validate(PlanPackageTypes::validationRules($request));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated = PlanPackageTypes::normalizePayload($validated);

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

        $validated = $request->validate(PlanPackageTypes::validationRules($request));
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }
        $validated = PlanPackageTypes::normalizePayload($validated);

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
