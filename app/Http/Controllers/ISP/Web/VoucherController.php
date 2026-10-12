<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\ISP\BaseController;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Voucher;
use App\Services\VoucherService;
use App\Support\Export\CsvExporter;
use App\Support\FormSettingsService;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends BaseController
{
    use InteractsWithListings;

    public function __construct(
        protected VoucherService $voucherService,
        protected FormSettingsService $formSettings,
    ) {}

    /**
     * Display a listing of vouchers
     */
    public function index(Request $request)
    {
        $ispId = $this->getIspId();

        $routers = Router::where('isp_id', $ispId)->get();
        $plans = Plan::where('isp_id', $ispId)->orderBy('name')->get(['id', 'name']);
        $routerLabels = $routers->pluck('name', 'id')->mapWithKeys(
            fn (string $name, int $id): array => [(string) $id => $name]
        )->all();

        $query = $this->filteredVouchersQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request, 25);
        $vouchers = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.vouchers.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'used' => 'Used',
                    'expired' => 'Expired',
                ],
            ],
            'router_id' => [
                'label' => 'Router',
                'values' => $routerLabels,
            ],
            'plan_id' => [
                'label' => 'Plan',
                'values' => $plans->mapWithKeys(fn ($plan) => [(string) $plan->id => $plan->name])->all(),
            ],
            'voucher_type' => [
                'label' => 'Type',
                'values' => ['time' => 'Time', 'data' => 'Data'],
            ],
            'date_from' => 'From',
            'date_to' => 'To',
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.vouchers.export.csv'));

        return view('isp.vouchers.index', compact('vouchers', 'routers', 'plans', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = $this->getIspId();
        $query = $this->filteredVouchersQuery($request, $ispId)->with(['router', 'plan']);
        $filename = 'vouchers-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Voucher Code', 'Type', 'Time Limit (Hours)', 'Data Limit (MB)', 'Speed Profile',
            'Price', 'Status', 'Expires At', 'Router', 'Plan', 'Created At', 'Used At',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($vouchers) use ($out) {
                foreach ($vouchers as $voucher) {
                    fputcsv($out, [
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
            });
        });
    }

    public function printCodes(Request $request)
    {
        $request->validate(['plan_id' => ['required', 'integer']]);
        $ispId = $this->getIspId();
        $plan = Plan::where('isp_id', $ispId)->findOrFail($request->integer('plan_id'));
        $vouchers = $this->filteredVouchersQuery($request, $ispId)->limit(1000)->get();

        return view('isp.vouchers.print-codes', compact('plan', 'vouchers'));
    }

    /**
     * @deprecated Use exportCsv via isp.vouchers.export.csv
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->exportCsv($request);
    }

    protected function filteredVouchersQuery(Request $request, int $ispId): Builder
    {
        $query = Voucher::where('isp_id', $ispId)
            ->with(['router', 'plan', 'creator', 'usedByCustomer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('voucher_code', 'like', $pattern)
                    ->orWhere('notes', 'like', $pattern);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
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
        $formFields = $this->formSettings->resolvedFields('voucher', $ispId);

        return view('isp.vouchers.create', compact('routers', 'plans', 'formFields'));
    }

    /**
     * Store a newly created voucher
     */
    public function store(Request $request)
    {
        $ispId = $this->getIspId();

        $this->formSettings->validateRequest($request, 'voucher', [
            'router_id' => ['exists:routers,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'voucher_type' => ['in:time,data'],
            'time_limit_hours' => ['required_if:voucher_type,time', 'integer', 'min:1'],
            'data_limit_mb' => ['required_if:voucher_type,data', 'integer', 'min:1'],
            'speed_profile' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'assigned_to' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $ispId);

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
            'plan_id' => 'nullable|exists:plans,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'code_prefix' => ['nullable', 'string', 'max:6', 'regex:/^[A-Za-z0-9]*$/'],
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
                    'code_prefix',
                ]),
                $plan,
                auth()->id()
            );

            if ($result['success_count'] > 0) {
                return redirect()->route('isp.vouchers.index', $plan ? ['plan_id' => $plan->id] : [])
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
     * Cancel an unused active voucher.
     */
    public function deactivate(Voucher $voucher)
    {
        $this->ensureIspAccess($voucher);

        if ($voucher->status !== 'active') {
            return back()->with('error', 'Only active unused vouchers can be deactivated.');
        }

        try {
            if (! $this->voucherService->deactivate($voucher)) {
                return back()->with('error', 'Failed to deactivate voucher.');
            }

            return back()->with('success', 'Voucher deactivated and removed from router when applicable.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to deactivate voucher: '.$e->getMessage());
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
}
