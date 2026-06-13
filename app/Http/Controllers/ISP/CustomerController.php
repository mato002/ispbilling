<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Services\MikroTikService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Customer::where('isp_id', $ispId)
            ->with(['router', 'activeSubscription.plan']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        return response()->json($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email',
            'location' => 'required|string',
            'connection_type' => 'required|in:hotspot,pppoe',
            'router_id' => 'required|exists:routers,id',
            'plan_id' => 'required|exists:plans,id',
            'username' => 'nullable|string|unique:customers,username',
            'password' => 'nullable|string|min:6',
            'installation_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $router = Router::findOrFail($validated['router_id']);

        // Verify router belongs to ISP
        if ($router->isp_id !== $ispId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $plainPassword = filled($validated['password'] ?? null)
                ? $validated['password']
                : Str::password(12);

            $customer = Customer::create([
                'isp_id' => $ispId,
                'router_id' => $validated['router_id'],
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'location' => $validated['location'],
                'connection_type' => $validated['connection_type'],
                'username' => $validated['username'] ?? null, // Will be auto-generated if null
                'password' => Hash::make($plainPassword),
                'status' => 'inactive',
                'installation_date' => $validated['installation_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create user on MikroTik router
            $mikrotik = new MikroTikService($router);

            $created = $validated['connection_type'] === 'hotspot'
                ? $mikrotik->createHotspotUser($customer, $plan, $plainPassword)
                : $mikrotik->createPppoeUser($customer, $plan, $plainPassword);

            if (! $created) {
                throw new \Exception('Failed to create user on router');
            }

            // Create subscription
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addDays($plan->duration_days),
                'renewal_date' => now()->addDays($plan->duration_days),
                'status' => 'active',
                'auto_renew' => true,
            ]);

            // Activate customer
            $mikrotik->enableUser($customer);
            $customer->activate();

            DB::commit();

            try {
                $this->notificationService->newCustomer($customer);
            } catch (\Throwable $e) {
                Log::warning('Failed to create ISP new-customer notification', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer created and activated successfully',
                'customer' => $customer->load(['router', 'activeSubscription.plan']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        // Verify customer belongs to user's ISP
        if ($customer->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $customer->load([
            'router',
            'subscriptions.plan',
            'invoices',
            'payments',
            'activeSubscription.plan',
        ]);

        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        // Verify customer belongs to user's ISP
        if ($customer->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'email' => 'nullable|email',
            'location' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'customer' => $customer,
        ]);
    }

    /**
     * Suspend customer
     */
    public function suspend(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (! $customer->router) {
            return response()->json(['error' => 'No router assigned'], 400);
        }

        try {
            $mikrotik = new MikroTikService($customer->router);
            $mikrotik->disconnectSession($customer);
            $mikrotik->disableUser($customer);
            $customer->suspend();

            return response()->json([
                'success' => true,
                'message' => 'Customer suspended successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend customer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate customer
     */
    public function activate(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (! $customer->router) {
            return response()->json(['error' => 'No router assigned'], 400);
        }

        try {
            $mikrotik = new MikroTikService($customer->router);
            $mikrotik->enableUser($customer);
            $customer->activate();

            return response()->json([
                'success' => true,
                'message' => 'Customer activated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate customer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            if ($customer->router && $customer->mikrotik_user_id) {
                $mikrotik = new MikroTikService($customer->router);
                $mikrotik->removeUser($customer);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer: '.$e->getMessage(),
            ], 500);
        }
    }
}
