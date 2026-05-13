<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalPromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GlobalPromoCodeController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $codes = GlobalPromoCode::query()->latest()->paginate(20);

        return view('admin.global-promo-codes.index', compact('codes'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.global-promo-codes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:64', 'unique:global_promo_codes,code'],
            'kind' => ['required', Rule::in([
                GlobalPromoCode::KIND_FIRST_MONTH_FREE,
                GlobalPromoCode::KIND_REFERRAL,
                GlobalPromoCode::KIND_PERCENT_OFF,
            ])],
            'title' => ['nullable', 'string', 'max:255'],
            'percent_off' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        GlobalPromoCode::query()->create($validated);

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code created.');
    }

    public function edit(GlobalPromoCode $globalPromoCode): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.global-promo-codes.edit', ['code' => $globalPromoCode]);
    }

    public function update(Request $request, GlobalPromoCode $globalPromoCode): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('global_promo_codes', 'code')->ignore($globalPromoCode->id)],
            'kind' => ['required', Rule::in([
                GlobalPromoCode::KIND_FIRST_MONTH_FREE,
                GlobalPromoCode::KIND_REFERRAL,
                GlobalPromoCode::KIND_PERCENT_OFF,
            ])],
            'title' => ['nullable', 'string', 'max:255'],
            'percent_off' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['code'] = strtoupper(trim($validated['code']));
        $globalPromoCode->update($validated);

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code updated.');
    }

    public function destroy(GlobalPromoCode $globalPromoCode): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $globalPromoCode->delete();

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code removed.');
    }
}
