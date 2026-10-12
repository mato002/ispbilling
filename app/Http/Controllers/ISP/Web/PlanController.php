<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Router;
use App\Services\MikroTikService;
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
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

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

        return view('isp.plans.index', compact('plans', 'filterChips', 'exportFormats', 'routers'));
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
        $validated['is_active'] = ! $plan->archived_at && $request->has('is_active');
        $validated = PlanPackageTypes::normalizePayload($validated);

        $plan->update($validated);

        return redirect()->route('isp.plans.index')->with('success', 'Plan updated successfully');
    }

    public function toggleActive(Plan $plan)
    {
        if ($plan->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if ($plan->archived_at) {
            return back()->with('error', 'Restore this archived plan before enabling it.');
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

        if ($plan->archived_at) {
            return back()->with('error', 'Restore this archived plan before enabling it.');
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

    public function togglePublic(Plan $plan)
    {
        $this->ensurePlanBelongsToCurrentIsp($plan);

        if ($plan->archived_at) {
            return back()->with('error', 'Restore this plan before changing its public listing.');
        }

        if (! $plan->customer_visible && ! $plan->is_active) {
            return back()->with('error', 'Enable this plan before publishing it to customers.');
        }

        $plan->update(['customer_visible' => ! $plan->customer_visible]);

        return back()->with('success', $plan->customer_visible ? 'Plan is now visible to customers.' : 'Plan hidden from public listings.');
    }

    public function toggleArchive(Plan $plan)
    {
        $this->ensurePlanBelongsToCurrentIsp($plan);

        if ($plan->archived_at) {
            $plan->update(['archived_at' => null, 'customer_visible' => false, 'is_active' => false]);
            return back()->with('success', 'Plan restored as inactive and hidden from customers.');
        }

        $plan->update(['archived_at' => now(), 'is_active' => false, 'customer_visible' => false]);

        return back()->with('success', 'Plan archived. Existing subscriptions and reports are preserved.');
    }

    public function duplicate(Plan $plan)
    {
        $this->ensurePlanBelongsToCurrentIsp($plan);

        $copy = $plan->replicate();
        $copy->name = $plan->name.' (Copy)';
        $copy->is_active = false;
        $copy->customer_visible = false;
        $copy->archived_at = null;
        $copy->save();

        return redirect()->route('isp.plans.index')->with('success', 'Plan copied as an inactive, unpublished draft. Review it before enabling or publishing.');
    }

    public function destroy(Plan $plan)
    {
        $this->ensurePlanBelongsToCurrentIsp($plan);

        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Plans with subscriber history cannot be deleted. Archive the plan instead.');
        }

        $plan->delete();

        return redirect()->route('isp.plans.index')->with('success', 'Plan deleted.');
    }

    public function syncProfile(Plan $plan)
    {
        $this->ensurePlanBelongsToCurrentIsp($plan);

        if ($plan->access_type !== 'hotspot') {
            return back()->with('error', 'Router profile sync is available for hotspot plans only.');
        }
        if (! trim((string) $plan->speed_profile)) {
            return back()->with('error', 'Set a MikroTik profile name on this plan before syncing it.');
        }

        $routers = Router::where('isp_id', $plan->isp_id)->where('is_active', true)->get();
        if ($routers->isEmpty()) {
            return back()->with('error', 'No active routers are configured for this ISP.');
        }

        $synced = 0;
        foreach ($routers as $router) {
            if ((new MikroTikService($router))->syncHotspotPlanProfile($plan)) {
                $synced++;
            }
        }

        return $synced === $routers->count()
            ? back()->with('success', 'NAS/MikroTik profile synchronized successfully on all active routers.')
            : back()->with('error', "Profile sync succeeded on {$synced} of {$routers->count()} active routers. Check router connectivity and configuration.");
    }

    private function ensurePlanBelongsToCurrentIsp(Plan $plan): void
    {
        abort_unless((int) $plan->isp_id === (int) auth()->user()->isp_id, 403);
    }
}
