<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\GlobalPromoCode;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GlobalPromoCodeController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = $this->filteredPromoCodesQuery($request);
        $perPage = $this->resolvePerPage($request, 20);
        $codes = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.promo-codes.index'), [
            'search' => 'Search',
            'kind' => [
                'label' => 'Kind',
                'values' => [
                    GlobalPromoCode::KIND_FIRST_MONTH_FREE => 'First month free',
                    GlobalPromoCode::KIND_REFERRAL => 'Referral',
                    GlobalPromoCode::KIND_PERCENT_OFF => 'Percent off',
                ],
            ],
            'is_active' => [
                'label' => 'Active',
                'values' => ['1' => 'Yes', '0' => 'No'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.promo-codes.export.csv'));

        return view('admin.global-promo-codes.index', compact('codes', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = $this->filteredPromoCodesQuery($request);

        return CsvExporter::download(
            'promo-codes-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Code', 'Kind', 'Title', 'Percent Off', 'Active', 'Redemptions', 'Max Redemptions', 'Starts', 'Ends', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            $row->code,
                            $row->kind,
                            $row->title,
                            $row->percent_off,
                            $row->is_active ? 'Yes' : 'No',
                            $row->redemption_count,
                            $row->max_redemptions,
                            $row->starts_at?->format('Y-m-d H:i:s'),
                            $row->ends_at?->format('Y-m-d H:i:s'),
                            $row->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredPromoCodesQuery(Request $request)
    {
        $query = GlobalPromoCode::query()->latest();

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('code', 'like', $pattern)
                    ->orWhere('title', 'like', $pattern);
            });
        }

        if ($request->filled('kind')) {
            $query->where('kind', $request->input('kind'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return $query;
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
