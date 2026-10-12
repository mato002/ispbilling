<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of available plans.
     */
    public function index(Request $request)
    {
        $plans = Plan::where('is_active', true)
            ->where('customer_visible', true)
            ->whereNull('archived_at')
            ->orderBy('price')
            ->get();

        return view('customer.plans.index', compact('plans'));
    }

    /**
     * Display the specified plan.
     */
    public function show(Request $request, Plan $plan)
    {
        // Only show active and customer-visible plans
        if (!$plan->is_active || !$plan->customer_visible || $plan->archived_at) {
            abort(404);
        }

        return view('customer.plans.show', compact('plan'));
    }
}
