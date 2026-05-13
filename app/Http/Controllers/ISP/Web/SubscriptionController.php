<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get();

        $query = Subscription::whereHas('customer', function ($q) use ($ispId) {
            $q->where('isp_id', $ispId);
        })->with(['customer', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($c) use ($search) {
                    $c->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })->orWhereHas('plan', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                });
            });
        }

        $subscriptions = $query->latest()->paginate(20)->appends($request->query());

        return view('isp.subscriptions.index', compact('subscriptions', 'plans'));
    }

    public function create(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $customers = Customer::where('isp_id', $ispId)->orderBy('full_name')->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get();

        $selectedCustomerId = $request->query('customer_id');
        if ($selectedCustomerId && ! Customer::where('isp_id', $ispId)->whereKey($selectedCustomerId)->exists()) {
            $selectedCustomerId = null;
        }

        return view('isp.subscriptions.create', compact('customers', 'plans', 'selectedCustomerId'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,suspended',
            'auto_renew' => 'nullable|boolean',
        ]);

        $customer = Customer::where('isp_id', $ispId)->findOrFail($validated['customer_id']);
        Plan::where('isp_id', $ispId)->where('is_active', true)->findOrFail($validated['plan_id']);

        if ($validated['status'] === 'active') {
            Subscription::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'auto_renew' => false]);
        }

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $validated['plan_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'renewal_date' => $validated['end_date'],
            'status' => $validated['status'],
            'auto_renew' => $request->boolean('auto_renew'),
        ]);

        return redirect()->route('isp.subscriptions.show', $subscription)
            ->with('success', 'Subscription created successfully.');
    }

    public function show(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $subscription->load(['customer', 'plan', 'invoices']);

        return view('isp.subscriptions.show', compact('subscription'));
    }

    public function renew(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            // Renew the subscription
            $subscription->renew();

            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('success', 'Subscription renewed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Failed to renew subscription: '.$e->getMessage());
        }
    }

    public function cancel(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            // Cancel the subscription
            $subscription->cancel();

            // Optionally suspend the customer on MikroTik
            $customer = $subscription->customer;
            if ($customer && $customer->router) {
                try {
                    $mikrotik = new \App\Services\MikroTikService($customer->router);
                    if ($customer->connection_type === 'hotspot') {
                        $mikrotik->disableHotspotUser($customer);
                    } else {
                        $mikrotik->disableUser($customer);
                    }
                    $customer->suspend();
                } catch (\Exception $e) {
                    // Log error but don't fail the cancellation
                    \Log::warning('Failed to suspend customer on router during subscription cancellation: '.$e->getMessage());
                }
            }

            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('success', 'Subscription cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Failed to cancel subscription: '.$e->getMessage());
        }
    }
}
