<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends BaseController
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Display a listing of vouchers
     */
    public function index(Request $request)
    {
        $ispId = $this->getIspId();

        $query = Voucher::where('isp_id', $ispId)
            ->with(['router', 'plan', 'creator', 'usedByCustomer'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by router
        if ($request->has('router_id') && $request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        // Filter by voucher type
        if ($request->has('voucher_type') && $request->voucher_type) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $vouchers = $query->paginate(50)->appends($request->query());

        // Get routers for filter dropdown
        $routers = Router::where('isp_id', $ispId)->get();

        return view('isp.vouchers.index', compact('vouchers', 'routers'));
    }

    /**
     * Show the form for creating a new voucher
     */
    public function create()
    {
        $ispId = $this->getIspId();
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)
            ->where('access_type', 'hotspot')
            ->where('is_active', true)
            ->get();

        return view('isp.vouchers.create', compact('routers', 'plans'));
    }

    /**
     * Store a newly created voucher
     */
    public function store(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'voucher_type' => 'required|in:time,data',
            'time_limit_hours' => 'required_if:voucher_type,time|integer|min:1',
            'data_limit_mb' => 'required_if:voucher_type,data|integer|min:1',
            'speed_profile' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
            'assigned_to' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $isp = auth()->user()->isp;
        $router = Router::findOrFail($request->router_id);

        // Ensure router belongs to ISP
        $this->ensureIspAccess($router);

        $plan = null;
        if ($request->has('plan_id') && $request->plan_id) {
            $plan = Plan::findOrFail($request->plan_id);
            $this->ensureIspAccess($plan);
        }

        try {
            $voucherData = $request->only([
                'voucher_type',
                'time_limit_hours',
                'data_limit_mb',
                'speed_profile',
                'price',
                'expires_at',
                'notes',
            ]);

            // Add assigned_to if provided
            if ($request->has('assigned_to') && $request->assigned_to) {
                $customer = \App\Models\Customer::findOrFail($request->assigned_to);
                $this->ensureIspAccess($customer);
                $voucherData['assigned_to'] = $request->assigned_to;
            }

            $voucher = $this->voucherService->generate(
                $isp,
                $router,
                $voucherData,
                $plan,
                auth()->id()
            );

            return redirect()->route('isp.vouchers.show', $voucher->id)
                ->with('success', 'Voucher created successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create voucher: '.$e->getMessage());
        }
    }

    /**
     * Display the specified voucher
     */
    public function show(Voucher $voucher)
    {
        $this->ensureIspAccess($voucher);

        $voucher->load(['router', 'plan', 'creator', 'usedByCustomer', 'isp']);

        return view('isp.vouchers.show', compact('voucher'));
    }

    /**
     * Show bulk creation form
     */
    public function createBulk()
    {
        $ispId = $this->getIspId();
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)
            ->where('access_type', 'hotspot')
            ->where('is_active', true)
            ->get();

        return view('isp.vouchers.create-bulk', compact('routers', 'plans'));
    }

    /**
     * Store bulk vouchers
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'voucher_type' => 'required|in:time,data',
            'time_limit_hours' => 'required_if:voucher_type,time|integer|min:1',
            'data_limit_mb' => 'required_if:voucher_type,data|integer|min:1',
            'speed_profile' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);

        $isp = auth()->user()->isp;
        $router = Router::findOrFail($request->router_id);

        // Ensure router belongs to ISP
        $this->ensureIspAccess($router);

        $plan = null;
        if ($request->has('plan_id') && $request->plan_id) {
            $plan = Plan::findOrFail($request->plan_id);
            $this->ensureIspAccess($plan);
        }

        try {
            $result = $this->voucherService->generateBulk(
                $isp,
                $router,
                $request->quantity,
                $request->only([
                    'voucher_type',
                    'time_limit_hours',
                    'data_limit_mb',
                    'speed_profile',
                    'price',
                    'expires_at',
                    'notes',
                ]),
                $plan,
                auth()->id()
            );

            if ($result['success_count'] > 0) {
                return redirect()->route('isp.vouchers.index')
                    ->with('success', "Successfully created {$result['success_count']} vouchers".
                        ($result['error_count'] > 0 ? ". {$result['error_count']} failed." : ''));
            } else {
                return back()->withInput()
                    ->with('error', 'Failed to create any vouchers. Please check the errors.');
            }
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create vouchers: '.$e->getMessage());
        }
    }

    /**
     * Activate/use a voucher
     */
    public function activate(Voucher $voucher)
    {
        $this->ensureIspAccess($voucher);

        if (! $voucher->isValid()) {
            return back()->with('error', 'Voucher is not valid or has expired');
        }

        try {
            $this->voucherService->activate($voucher);

            return back()->with('success', 'Voucher activated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to activate voucher: '.$e->getMessage());
        }
    }

    /**
     * Delete a voucher
     */
    public function destroy(Voucher $voucher)
    {
        $this->ensureIspAccess($voucher);

        try {
            // Delete from router if exists
            $this->voucherService->deleteFromRouter($voucher);

            $voucher->delete();

            return redirect()->route('isp.vouchers.index')
                ->with('success', 'Voucher deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete voucher: '.$e->getMessage());
        }
    }

    /**
     * Export vouchers (CSV)
     */
    public function export(Request $request)
    {
        $ispId = $this->getIspId();

        $query = Voucher::where('isp_id', $ispId);

        // Apply same filters as index
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('router_id') && $request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        $vouchers = $query->with(['router', 'plan'])->get();

        $filename = 'vouchers_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($vouchers) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Voucher Code',
                'Type',
                'Time Limit (Hours)',
                'Data Limit (MB)',
                'Speed Profile',
                'Price',
                'Status',
                'Expires At',
                'Router',
                'Plan',
                'Created At',
                'Used At',
            ]);

            // Data rows
            foreach ($vouchers as $voucher) {
                fputcsv($file, [
                    $voucher->voucher_code,
                    $voucher->voucher_type,
                    $voucher->time_limit_hours ?? '',
                    $voucher->data_limit_mb ?? '',
                    $voucher->speed_profile ?? '',
                    $voucher->price ?? '',
                    $voucher->status,
                    $voucher->expires_at?->format('Y-m-d H:i:s') ?? '',
                    $voucher->router->name ?? '',
                    $voucher->plan->name ?? '',
                    $voucher->created_at->format('Y-m-d H:i:s'),
                    $voucher->used_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
