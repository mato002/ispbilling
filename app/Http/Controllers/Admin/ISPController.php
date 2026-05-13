<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SuspendEntireIspNetworkJob;
use App\Models\ISP;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ISPController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = ISP::withCount(['customers', 'routers', 'plans']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $isps = $query->latest()->paginate(20);

        return view('admin.isps.index', compact('isps'));
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
            'saas_onboarding_completed_at' => now(),
            'saas_subscription_status' => 'active',
        ]));

        return redirect()->route('admin.isps.index')->with('success', 'ISP created successfully');
    }

    public function show(ISP $isp)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isp->loadCount(['customers', 'routers', 'plans', 'invoices', 'payments', 'users']);
        $isp->load(['paymentMethods' => fn ($q) => $q->orderBy('provider')]);

        return view('admin.isps.show', compact('isp'));
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
