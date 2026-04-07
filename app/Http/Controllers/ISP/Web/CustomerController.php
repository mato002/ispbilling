<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Router;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $query = Customer::where('isp_id', $ispId)
            ->with(['router', 'subscriptions' => function ($q) {
                $q->where('status', 'active')
                  ->where('end_date', '>=', now())
                  ->with('plan')
                  ->latest();
            }]);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->get();

        return view('isp.customers.index', compact('customers', 'routers', 'plans'));
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->get();
        return view('isp.customers.create', compact('routers', 'plans'));
    }

    public function store(Request $request)
    {
        // Placeholder - will be implemented
        return redirect()->route('isp.customers.index')->with('success', 'Customer created successfully');
    }

    public function show(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $customer->load([
            'router', 
            'subscriptions' => function ($q) {
                $q->where('status', 'active')
                  ->where('end_date', '>=', now())
                  ->with('plan')
                  ->latest();
            }
        ]);
        return view('isp.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->get();
        return view('isp.customers.edit', compact('customer', 'routers', 'plans'));
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        // Placeholder - will be implemented
        return redirect()->route('isp.customers.index')->with('success', 'Customer updated successfully');
    }

    /**
     * Suspend customer
     */
    public function suspend(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if (!$customer->router) {
            return back()->with('error', 'No router assigned to this customer.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);
            $mikrotik->disconnectSession($customer);
            $mikrotik->disableUser($customer);
            $customer->suspend();

            return back()->with('success', 'Customer suspended successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to suspend customer: ' . $e->getMessage());
        }
    }

    /**
     * Activate customer
     */
    public function activate(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if (!$customer->router) {
            return back()->with('error', 'No router assigned to this customer.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);
            if ($customer->connection_type === 'hotspot') {
                $mikrotik->activateHotspotUser($customer);
            } else {
                $mikrotik->enableUser($customer);
            }
            $customer->activate();

            return back()->with('success', 'Customer activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to activate customer: ' . $e->getMessage());
        }
    }
}

