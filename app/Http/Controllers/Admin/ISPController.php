<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Jobs\SuspendEntireIspNetworkJob;
use App\Models\ISP;
use App\Models\User;
use App\Services\IspAdminOverviewService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ISPController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = $this->filteredIspQuery($request);
        $perPage = $this->resolvePerPage($request);
        $isps = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.isps.index'), [
            'search' => 'Search',
            'is_active' => [
                'label' => 'Status',
                'values' => ['1' => 'Active', '0' => 'Inactive'],
            ],
            'saas_status' => [
                'label' => 'SaaS',
                'values' => [
                    'none' => 'None',
                    'active' => 'Active',
                    'trialing' => 'Trialing',
                    'past_due' => 'Past due',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ],
            ],
            'onboarding' => [
                'label' => 'Onboarding',
                'values' => [
                    'complete' => 'Complete',
                    'pending' => 'Pending',
                ],
            ],
            'sort' => [
                'label' => 'Sort',
                'values' => [
                    'latest' => 'Newest first',
                    'oldest' => 'Oldest first',
                    'name_az' => 'Name A–Z',
                    'name_za' => 'Name Z–A',
                    'customers_desc' => 'Most customers',
                ],
            ],
            'created_from' => 'Created from',
            'created_to' => 'Created to',
        ]);

        $exportFormats = $this->listingExportFormats(
            $request,
            route('admin.isps.export.csv'),
            'Export CSV',
        );

        return view('admin.isps.index', compact('isps', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = $this->filteredIspQuery($request);

        return CsvExporter::download(
            'isps-'.now()->format('Ymd-His').'.csv',
            [
                'ID', 'Name', 'Company', 'Email', 'Phone', 'Slug', 'Status',
                'Customers', 'Routers', 'Plans', 'SaaS Status', 'Onboarding',
                'Currency', 'Created',
            ],
            function ($out) use ($query) {
                $query->chunk(200, function ($isps) use ($out) {
                    foreach ($isps as $isp) {
                        fputcsv($out, [
                            $isp->id,
                            $isp->name,
                            $isp->company_name,
                            $isp->email,
                            $isp->phone,
                            $isp->slug,
                            $isp->is_active ? 'Active' : 'Inactive',
                            $isp->customers_count,
                            $isp->routers_count,
                            $isp->plans_count,
                            $isp->saas_subscription_status ?? 'none',
                            $isp->saas_onboarding_completed_at ? 'Complete' : 'Pending',
                            $isp->currency,
                            $isp->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredIspQuery(Request $request)
    {
        $query = ISP::withCount(['customers', 'routers', 'plans']);

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('name', 'like', $pattern)
                    ->orWhere('company_name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern)
                    ->orWhere('phone', 'like', $pattern)
                    ->orWhere('slug', 'like', $pattern);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('saas_status')) {
            $query->where('saas_subscription_status', $request->saas_status);
        }

        if ($request->filled('onboarding')) {
            if ($request->onboarding === 'complete') {
                $query->whereNotNull('saas_onboarding_completed_at');
            } elseif ($request->onboarding === 'pending') {
                $query->whereNull('saas_onboarding_completed_at');
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest(),
            'name_az' => $query->orderBy('name'),
            'name_za' => $query->orderByDesc('name'),
            'customers_desc' => $query->orderByDesc('customers_count')->orderBy('name'),
            default => $query->latest(),
        };

        return $query;
    }

    public function create()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('admin.isps.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:i_s_p_s,slug'],
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:i_s_p_s,email',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'currency' => 'required|string|in:KES,USD,TZS,UGX',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $slug = $validated['slug'] ?? null;
        if ($slug === null || $slug === '') {
            $base = Str::slug($validated['name']);
            $slug = $base !== '' ? $base : 'isp';
            $suffix = 0;
            while (ISP::query()->where('slug', $slug)->exists()) {
                $suffix++;
                $slug = $base.'-'.$suffix;
            }
        }
        $validated['slug'] = $slug;

        $isp = ISP::create(array_merge($validated, [
            'saas_subscription_status' => 'none',
        ]));

        return redirect()
            ->route('admin.isps.saas-onboarding.show', $isp)
            ->with(
                'success',
                'ISP created. Complete the same PrimeBilling SaaS onboarding (plan, billing, payment details) you would capture on self-service—then add operators if you have not already.'
            );
    }

    public function show(ISP $isp, IspAdminOverviewService $overview)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $overviewData = $overview->build($isp);

        return view('admin.isps.show', array_merge(['isp' => $isp], $overviewData));
    }

    public function edit(ISP $isp)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('admin.isps.edit', compact('isp'));
    }

    public function update(Request $request, ISP $isp)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('i_s_p_s', 'slug')->ignore($isp->id)],
            'company_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('i_s_p_s', 'email')->ignore($isp->id)],
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'currency' => 'required|string|in:KES,USD,TZS,UGX',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
            'platform_access_locked' => 'nullable|boolean',
            'platform_access_message' => 'nullable|string|max:2000',
            'saas_tier' => 'nullable|string|max:32',
        ]);

        if ($validated['slug'] === null || $validated['slug'] === '') {
            unset($validated['slug']);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['platform_access_locked'] = $request->has('platform_access_locked');
        $validated['platform_access_message'] = $validated['platform_access_message'] ?? null;
        $validated['saas_tier'] = $validated['saas_tier'] ?? null;

        $wasActive = (bool) $isp->is_active;

        $isp->update($validated);
        $isp->refresh();

        if ($wasActive && ! $isp->is_active) {
            User::query()->where('isp_id', $isp->id)->update(['is_active' => false]);
            SuspendEntireIspNetworkJob::dispatch($isp->id);
        }

        if (! $wasActive && $isp->is_active) {
            User::query()->where('isp_id', $isp->id)->update(['is_active' => true]);
        }

        return redirect()->route('admin.isps.index')->with('success', 'ISP updated successfully');
    }

    /**
     * Re-queue full-network suspension (e.g. router was offline during initial deactivation).
     */
    public function forceSuspendNetwork(ISP $isp)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        if ($isp->is_active) {
            return redirect()
                ->route('admin.isps.show', $isp)
                ->with('error', 'Only inactive ISPs can be force-synced for suspension.');
        }

        SuspendEntireIspNetworkJob::dispatch($isp->id);

        return redirect()
            ->route('admin.isps.show', $isp)
            ->with('success', 'Network suspension job has been queued for '.$isp->name.'.');
    }

    public function destroy(ISP $isp)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isp->delete();

        return redirect()->route('admin.isps.index')->with('success', 'ISP deleted successfully');
    }
}
