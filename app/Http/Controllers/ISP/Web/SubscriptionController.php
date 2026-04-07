<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $query = Subscription::whereHas('customer', function ($q) use ($ispId) {
            $q->where('isp_id', $ispId);
        })->with(['customer', 'plan']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->latest()->paginate(20);

        return view('isp.subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $customers = \App\Models\Customer::where('isp_id', $ispId)->get();
        $plans = \App\Models\Plan::where('isp_id', $ispId)->where('is_active', true)->get();
        return view('isp.subscriptions.create', compact('customers', 'plans'));
    }

    public function store(Request $request)
    {
        // Placeholder - will be implemented
        return redirect()->route('isp.subscriptions.index')->with('success', 'Subscription created successfully');
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
                ->with('error', 'Failed to renew subscription: ' . $e->getMessage());
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
                    \Log::warning('Failed to suspend customer on router during subscription cancellation: ' . $e->getMessage());
                }
            }
            
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('success', 'Subscription cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Failed to cancel subscription: ' . $e->getMessage());
        }
    }
}
