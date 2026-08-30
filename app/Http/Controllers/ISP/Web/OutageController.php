<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Outage;
use App\Models\Router;
use App\Services\OutageCompensationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OutageController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected OutageCompensationService $compensationService,
    ) {}

    public function index(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $query = $this->filteredOutagesQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $outages = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.outages.index'), [
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'resolved' => 'Resolved',
                ],
            ],
        ]);

        return view('isp.outages.index', compact('outages', 'filterChips'));
    }

    protected function filteredOutagesQuery(Request $request, int $ispId): Builder
    {
        return Outage::forIsp($ispId)
            ->with(['router', 'createdBy'])
            ->withCount('compensationLogs')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('started_at');
    }

    public function create(): View
    {
        $ispId = Auth::user()->isp_id;
        $routers = Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name', 'ip_address']);

        return view('isp.outages.create', compact('routers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $ispId = Auth::user()->isp_id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'router_id' => 'nullable|exists:routers,id',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:active,resolved',
        ]);

        if (! empty($validated['router_id'])) {
            Router::forIsp($ispId)->whereKey($validated['router_id'])->firstOrFail();
        }

        $outage = Outage::create([
            'isp_id' => $ispId,
            'router_id' => $validated['router_id'] ?? null,
            'created_by_user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('isp.outages.show', $outage)
            ->with('success', 'Outage recorded. Review affected customers and apply compensation manually when ready.');
    }

    public function show(Outage $outage): View
    {
        $this->authorizeOutage($outage);

        $outage->load(['router', 'createdBy', 'compensationLogs.admin', 'compensationLogs.customer']);

        $affectedCustomers = $this->compensationService->affectedCustomers($outage);

        return view('isp.outages.show', compact('outage', 'affectedCustomers'));
    }

    public function resolve(Outage $outage): RedirectResponse
    {
        $this->authorizeOutage($outage);

        $outage->update([
            'status' => Outage::STATUS_RESOLVED,
            'ended_at' => $outage->ended_at ?? now(),
        ]);

        return back()->with('success', 'Outage marked as resolved.');
    }

    public function applyCompensation(Request $request, Outage $outage): RedirectResponse
    {
        $this->authorizeOutage($outage);

        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'compensation_type' => 'required|in:subscription_extension,voucher_extension',
            'duration_value' => 'required|integer|min:1|max:365',
            'duration_unit' => 'required|in:hours,days',
        ]);

        $result = $this->compensationService->applyCompensation(
            $outage,
            $validated['customer_ids'],
            $validated['compensation_type'],
            (int) $validated['duration_value'],
            $validated['duration_unit'],
            Auth::user(),
        );

        $message = "Compensation applied to {$result['applied']} customer(s).";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped.";
        }

        $redirect = back()->with('success', $message);

        if (! empty($result['errors'])) {
            $redirect->with('compensation_errors', $result['errors']);
        }

        return $redirect;
    }

    protected function authorizeOutage(Outage $outage): void
    {
        if ($outage->isp_id !== Auth::user()->isp_id) {
            abort(403);
        }
    }
}
