<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSaasPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformSaasPlanController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plans = PlatformSaasPlan::query()->orderBy('sort_order')->orderBy('id')->paginate(30);

        return view('admin.platform-saas-plans.index', compact('plans'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.platform-saas-plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9\-]+$/', 'unique:platform_saas_plans,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['features'] = [
            'advanced_analytics' => $request->boolean('advanced_analytics'),
            'sms_automation' => $request->boolean('sms_automation'),
        ];
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;

        PlatformSaasPlan::query()->create($validated);

        return redirect()->route('admin.platform-saas-plans.index')->with('success', 'Plan created.');
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
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['features'] = [
            'advanced_analytics' => $request->boolean('advanced_analytics'),
            'sms_automation' => $request->boolean('sms_automation'),
        ];
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;

        $platformSaasPlan->update($validated);

        return redirect()->route('admin.platform-saas-plans.index')->with('success', 'Plan updated.');
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
