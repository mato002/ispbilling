<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesModalFormResponses;
use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\PlatformSaasPlan;
use App\Models\PlatformSetting;
use App\Services\Admin\PlatformSaasPlanOverviewService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformSaasPlanController extends Controller
{
    use HandlesModalFormResponses;
    use InteractsWithListings;

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = $this->filteredPlansQuery($request);
        $perPage = $this->resolvePerPage($request, 30);
        $plans = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.platform-saas-plans.index'), [
            'search' => 'Search',
            'is_active' => [
                'label' => 'Active',
                'values' => ['1' => 'Yes', '0' => 'No'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.platform-saas-plans.export.csv'));

        return view('admin.platform-saas-plans.index', compact('plans', 'filterChips', 'exportFormats'));
    }

    public function show(PlatformSaasPlan $platformSaasPlan, PlatformSaasPlanOverviewService $overview): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $dashboard = $overview->build($platformSaasPlan);

        return view('admin.platform-saas-plans.show', [
            'plan' => $platformSaasPlan,
            'stats' => $dashboard['stats'],
            'statusBreakdown' => $dashboard['status_breakdown'],
            'alerts' => $dashboard['alerts'],
            'tenants' => $dashboard['tenants'],
            'recentInvoices' => $dashboard['recent_invoices'],
            'monthlySignups' => $dashboard['monthly_signups'],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = $this->filteredPlansQuery($request);

        return CsvExporter::download(
            'platform-saas-plans-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Name', 'Slug', 'Max Customers', 'Monthly Price', 'Setup Fee', 'Trial Days', 'Sort Order', 'Active', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            $row->name,
                            $row->slug,
                            $row->max_customers,
                            $row->monthly_price,
                            $row->setup_fee,
                            $row->trial_days,
                            $row->sort_order,
                            $row->is_active ? 'Yes' : 'No',
                            $row->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredPlansQuery(Request $request)
    {
        $query = PlatformSaasPlan::query()->orderBy('sort_order')->orderBy('id');

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('name', 'like', $pattern)
                    ->orWhere('slug', 'like', $pattern);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return $query;
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $defaultTrialDays = PlatformSetting::getInt('saas_default_trial_days', 14);

        return view('admin.platform-saas-plans.create', compact('defaultTrialDays'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9\-]+$/', 'unique:platform_saas_plans,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_tenants' => ['nullable', 'integer', 'min:1'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'max_routers' => ['nullable', 'integer', 'min:1'],
            'speed_limit_mbps' => ['nullable', 'integer', 'min:1'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['features'] = $this->planFeaturesFromRequest($request);
        unset($validated['max_tenants'], $validated['max_routers'], $validated['speed_limit_mbps']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;

        PlatformSaasPlan::query()->create($validated);

        return $this->modalOrRedirect(
            'Plan created.',
            redirect()->route('admin.platform-saas-plans.index'),
        );
    }

    public function edit(PlatformSaasPlan $platformSaasPlan): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.platform-saas-plans.edit', ['plan' => $platformSaasPlan]);
    }

    public function update(Request $request, PlatformSaasPlan $platformSaasPlan): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9\-]+$/', Rule::unique('platform_saas_plans', 'slug')->ignore($platformSaasPlan->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_tenants' => ['nullable', 'integer', 'min:1'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'max_routers' => ['nullable', 'integer', 'min:1'],
            'speed_limit_mbps' => ['nullable', 'integer', 'min:1'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['features'] = $this->planFeaturesFromRequest($request);
        unset($validated['max_tenants'], $validated['max_routers'], $validated['speed_limit_mbps']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;

        $platformSaasPlan->update($validated);

        return redirect()->route('admin.platform-saas-plans.index')->with('success', 'Plan updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function planFeaturesFromRequest(Request $request): array
    {
        return [
            'advanced_analytics' => $request->boolean('advanced_analytics'),
            'sms_automation' => $request->boolean('sms_automation'),
            'max_tenants' => $request->filled('max_tenants') ? (int) $request->input('max_tenants') : null,
            'max_routers' => $request->filled('max_routers') ? (int) $request->input('max_routers') : null,
            'speed_limit_mbps' => $request->filled('speed_limit_mbps') ? (int) $request->input('speed_limit_mbps') : null,
        ];
    }

    public function destroy(PlatformSaasPlan $platformSaasPlan): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($platformSaasPlan->isps()->exists()) {
            return back()->with('error', 'Cannot delete a plan that is assigned to tenants.');
        }

        $platformSaasPlan->delete();

        return redirect()->route('admin.platform-saas-plans.index')->with('success', 'Plan deleted.');
    }
}
