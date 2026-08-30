<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Export\CsvExporter;
use App\Support\FormSettingsService;
use App\Support\PlanPackageTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanController extends Controller
{
    use InteractsWithListings;

    public function __construct(private readonly FormSettingsService $formSettings) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = $this->filteredPlansQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $plans = $query->latest()->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.plans.index'), [
            'package_type' => [
                'label' => 'Package type',
                'values' => PlanPackageTypes::labels(),
            ],
            'access_type' => [
                'label' => 'Access type',
                'values' => ['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE'],
            ],
            'is_active' => [
                'label' => 'Status',
                'values' => ['1' => 'Active', '0' => 'Inactive'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.plans.export.csv'));

        return view('isp.plans.index', compact('plans', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredPlansQuery($request, $ispId)->latest();
        $filename = 'plans-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'ID', 'Name', 'Package type', 'Access type', 'Billing type', 'Price', 'Duration (days)',
            'Download (Kbps)', 'Upload (Kbps)', 'Data limit (MB)', 'One-time', 'Recurring', 'Active', 'Subscriptions', 'Created',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($plans) use ($out) {
                foreach ($plans as $plan) {
                    fputcsv($out, [
                        $plan->id,
                        $plan->name,
                        $plan->package_type,
                        $plan->access_type,
                        $plan->billing_type,
                        $plan->price,
                        $plan->duration_days,
                        $plan->download_speed_kbps,
                        $plan->upload_speed_kbps,
                        $plan->data_limit_mb,
                        $plan->is_one_time ? 'Yes' : 'No',
                        $plan->is_recurring ? 'Yes' : 'No',
                        $plan->is_active ? 'Active' : 'Inactive',
                        $plan->subscriptions_count,
                        $plan->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredPlansQuery(Request $request, int $ispId): Builder
    {
        $query = Plan::where('isp_id', $ispId)->withCount('subscriptions');

        if ($request->filled('package_type')) {
            $query->where('package_type', $request->package_type);
        }

        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        return $query;
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $formFields = $this->formSettings->resolvedFields('plan', $ispId);

        return view('isp.plans.create', compact('formFields'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $this->formSettings->validateRequest(
            $request,
            'plan',
            PlanPackageTypes::validationRules($request),
            $ispId
        );
        $validated['is_active'] = $request->has('is_active');
        $validated = PlanPackageTypes::normalizePayload($validated);
        $validated['isp_id'] = $ispId;

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

        $formFields = $this->formSettings->resolvedFields('plan', $plan->isp_id);

        return view('isp.plans.edit', compact('plan', 'formFields'));
    }

    public function update(Request $request, Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $validated = $this->formSettings->validateRequest(
            $request,
            'plan',
            PlanPackageTypes::validationRules($request),
            $plan->isp_id
        );
        $validated['is_active'] = $request->has('is_active');
        $validated = PlanPackageTypes::normalizePayload($validated);

        $plan->update($validated);

        return redirect()->route('isp.plans.index')->with('success', 'Plan updated successfully');
    }

    public function toggleActive(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        $message = $plan->is_active
            ? 'Plan activated successfully.'
            : 'Plan deactivated successfully.';

        return back()->with('success', $message);
    }

    public function activate(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => true]);

        return back()->with('success', 'Plan activated successfully.');
    }

    public function deactivate(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $plan->update(['is_active' => false]);

        return back()->with('success', 'Plan deactivated successfully.');
    }
}
