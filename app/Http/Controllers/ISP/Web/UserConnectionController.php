<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\UserConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserConnectionController extends Controller
{
    protected $connectionService;

    public function __construct(UserConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Display user connection management dashboard
     */
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        // Get connection statistics
        $stats = [
            'total_customers' => Customer::forIsp($ispId)->count(),
            'active_customers' => Customer::forIsp($ispId)->where('status', 'active')->count(),
            'suspended_customers' => Customer::forIsp($ispId)->where('status', 'suspended')->count(),
            'expired_customers' => Customer::forIsp($ispId)->where('status', 'expired')->count(),
            'inactive_customers' => Customer::forIsp($ispId)->where('status', 'inactive')->count(),
            'expired_subscriptions' => Subscription::whereHas('customer', function ($query) use ($ispId) {
                $query->where('isp_id', $ispId);
            })->where('status', 'active')->where('end_date', '<', now())->count(),
        ];

        // Get recent connection activities
        $recentActivities = Customer::forIsp($ispId)
            ->with(['router', 'subscriptions'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return view('isp.user-connections.index', compact('stats', 'recentActivities'));
    }

    /**
     * Manually disconnect a user
     */
    public function disconnect(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            $service = new UserConnectionService($customer->router);
            $result = $service->disconnectUser($customer);

            if ($result) {
                $customer->update([
                    'connection_status' => 'disconnected',
                    'disconnected_at' => now(),
                ]);

                return back()->with('success', 'User disconnected successfully');
            }

            return back()->with('error', 'Failed to disconnect user');

        } catch (\Exception $e) {
            Log::error("Manual disconnect error for customer {$customer->id}: " . $e->getMessage());
            return back()->with('error', 'Error disconnecting user: ' . $e->getMessage());
        }
    }

    /**
     * Manually suspend a user
     */
    public function suspend(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            $service = new UserConnectionService($customer->router);
            $result = $service->suspendUser($customer);

            if ($result) {
                $customer->update([
                    'status' => 'suspended',
                    'connection_status' => 'suspended',
                    'suspended_at' => now(),
                ]);

                return back()->with('success', 'User suspended successfully');
            }

            return back()->with('error', 'Failed to suspend user');

        } catch (\Exception $e) {
            Log::error("Manual suspend error for customer {$customer->id}: " . $e->getMessage());
            return back()->with('error', 'Error suspending user: ' . $e->getMessage());
        }
    }

    /**
     * Manually reconnect a user
     */
    public function reconnect(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            // Check if customer has active subscription
            $activeSubscription = $customer->subscriptions()
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now())
                ->first();

            if (!$activeSubscription) {
                return back()->with('error', 'Customer has no active subscription');
            }

            $service = new UserConnectionService($customer->router);
            $result = $service->reconnectUser($customer, $activeSubscription);

            if ($result) {
                $customer->update([
                    'status' => 'active',
                    'connection_status' => 'connected',
                    'reconnected_at' => now(),
                ]);

                // Apply bandwidth limits
                $service->applyBandwidthLimits($customer, $activeSubscription);

                return back()->with('success', 'User reconnected successfully');
            }

            return back()->with('error', 'Failed to reconnect user');

        } catch (\Exception $e) {
            Log::error("Manual reconnect error for customer {$customer->id}: " . $e->getMessage());
            return back()->with('error', 'Error reconnecting user: ' . $e->getMessage());
        }
    }

    /**
     * Get real-time connection status for a user
     */
    public function getStatus(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            $service = new UserConnectionService($customer->router);
            $status = $service->getUserConnectionStatus($customer);

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error("Status check error for customer {$customer->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Run automatic user management
     */
    public function runAutomatic(Request $request)
    {
        try {
            // Dispatch the job to handle automatic user management
            \App\Jobs\ManageUserConnections::dispatch();

            return back()->with('success', 'Automatic user management job dispatched');

        } catch (\Exception $e) {
            Log::error("Automatic user management error: " . $e->getMessage());
            return back()->with('error', 'Error running automatic user management: ' . $e->getMessage());
        }
    }

    /**
     * Get connection statistics
     */
    public function getStatistics(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $stats = [
            'total_customers' => Customer::forIsp($ispId)->count(),
            'connected_customers' => Customer::forIsp($ispId)->where('connection_status', 'connected')->count(),
            'suspended_customers' => Customer::forIsp($ispId)->where('connection_status', 'suspended')->count(),
            'disconnected_customers' => Customer::forIsp($ispId)->where('connection_status', 'disconnected')->count(),
            'expired_subscriptions' => Subscription::whereHas('customer', function ($query) use ($ispId) {
                $query->where('isp_id', $ispId);
            })->where('status', 'active')->where('end_date', '<', now())->count(),
            'unpaid_invoices' => \App\Models\Invoice::whereHas('customer', function ($query) use ($ispId) {
                $query->where('isp_id', $ispId);
            })->whereIn('status', ['pending', 'overdue'])->where('due_date', '<', now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Batch operations on multiple users
     */
    public function batchOperation(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'operation' => 'required|in:disconnect,suspend,reconnect',
        ]);

        $customerIds = $request->customer_ids;
        $operation = $request->operation;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($customerIds as $customerId) {
            $customer = Customer::find($customerId);
            
            if ($customer->isp_id !== auth()->user()->isp_id) {
                $errorCount++;
                continue;
            }

            try {
                $service = new UserConnectionService($customer->router);

                switch ($operation) {
                    case 'disconnect':
                        $result = $service->disconnectUser($customer);
                        if ($result) {
                            $customer->update([
                                'connection_status' => 'disconnected',
                                'disconnected_at' => now(),
                            ]);
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                        break;

                    case 'suspend':
                        $result = $service->suspendUser($customer);
                        if ($result) {
                            $customer->update([
                                'status' => 'suspended',
                                'connection_status' => 'suspended',
                                'suspended_at' => now(),
                            ]);
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                        break;

                    case 'reconnect':
                        $activeSubscription = $customer->subscriptions()
                            ->where('status', 'active')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>', now())
                            ->first();

                        if ($activeSubscription) {
                            $result = $service->reconnectUser($customer, $activeSubscription);
                            if ($result) {
                                $customer->update([
                                    'status' => 'active',
                                    'connection_status' => 'connected',
                                    'reconnected_at' => now(),
                                ]);
                                $service->applyBandwidthLimits($customer, $activeSubscription);
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                        } else {
                            $errors[] = "Customer {$customer->username} has no active subscription";
                            $errorCount++;
                        }
                        break;
                }

            } catch (\Exception $e) {
                $errors[] = "Error with customer {$customer->username}: " . $e->getMessage();
                $errorCount++;
            }
        }

        $message = "Batch {$operation} completed. Success: {$successCount}, Errors: {$errorCount}";
        
        if (!empty($errors)) {
            $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= " and " . (count($errors) - 3) . " more...";
            }
        }

        return back()->with('info', $message);
    }
}
