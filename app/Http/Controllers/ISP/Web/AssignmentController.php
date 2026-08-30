<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\Router;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AssignmentController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $query = $this->filteredAssignmentsQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $assignments = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.inventory.assignments.index'), [
            'q' => 'Search',
            'installation_status' => [
                'label' => 'Installation',
                'values' => InventoryCatalog::INSTALLATION_STATUSES,
            ],
            'active' => [
                'label' => 'Assignment',
                'values' => ['1' => 'Active', '0' => 'Closed'],
            ],
        ]);

        return view('isp.inventory.assignments.index', compact('assignments', 'filterChips'));
    }

    public function create(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $availableItems = InventoryItem::forIsp($ispId)
            ->available()
            ->orderBy('name')
            ->get(['id', 'name', 'serial_number', 'category', 'brand', 'model']);

        $customers = Customer::forIsp($ispId)
            ->orderBy('full_name')
            ->limit(500)
            ->get(['id', 'full_name', 'phone_number', 'router_id']);

        $routers = Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name', 'ip_address']);

        $technicians = User::where('isp_id', $ispId)
            ->whereIn('role', ['isp_admin', 'isp_staff', 'isp_technician'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('isp.inventory.assignments.create', compact(
            'availableItems',
            'customers',
            'routers',
            'technicians',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $ispId = Auth::user()->isp_id;

        $validated = $request->validate([
            'inventory_item_id' => [
                'required',
                Rule::exists('inventory_items', 'id')->where('isp_id', $ispId),
            ],
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('isp_id', $ispId),
            ],
            'router_id' => [
                'nullable',
                Rule::exists('routers', 'id')->where('isp_id', $ispId),
            ],
            'technician_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('isp_id', $ispId),
            ],
            'installation_date' => 'nullable|date',
            'installation_location' => 'nullable|string|max:500',
            'installation_gps' => 'nullable|string|max:100',
            'installation_status' => ['required', Rule::in(array_keys(InventoryCatalog::INSTALLATION_STATUSES))],
            'notes' => 'nullable|string|max:2000',
        ]);

        $item = InventoryItem::forIsp($ispId)->whereKey($validated['inventory_item_id'])->firstOrFail();
        $customer = Customer::forIsp($ispId)->whereKey($validated['customer_id'])->firstOrFail();

        try {
            $assignment = $this->inventoryService->assignItem($item, $customer, $validated, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.inventory.assignments.show', $assignment)
            ->with('success', 'Device assigned to customer.');
    }

    public function show(InventoryAssignment $assignment): View
    {
        $this->authorizeAssignment($assignment);

        $assignment->load([
            'item',
            'customer',
            'router',
            'technician',
            'assignedBy',
            'replacedByAssignment.item',
            'transactions' => fn ($q) => $q->with('performedBy')->latest('created_at'),
        ]);

        $replacementItems = InventoryItem::forIsp($assignment->isp_id)
            ->available()
            ->where('id', '!=', $assignment->inventory_item_id)
            ->orderBy('name')
            ->get(['id', 'name', 'serial_number', 'category']);

        return view('isp.inventory.assignments.show', compact('assignment', 'replacementItems'));
    }

    public function completeInstallation(InventoryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($assignment);

        try {
            $this->inventoryService->completeInstallation($assignment, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Installation completed and internet service linked to customer.');
    }

    public function returnDevice(Request $request, InventoryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($assignment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->returnItem($assignment, Auth::user(), $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.inventory.assignments.index')
            ->with('success', 'Device returned to warehouse.');
    }

    public function replace(Request $request, InventoryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($assignment);

        $validated = $request->validate([
            'replacement_item_id' => [
                'required',
                Rule::exists('inventory_items', 'id')->where('isp_id', $assignment->isp_id),
            ],
            'technician_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('isp_id', $assignment->isp_id),
            ],
            'installation_location' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $replacementItem = InventoryItem::forIsp($assignment->isp_id)
            ->whereKey($validated['replacement_item_id'])
            ->firstOrFail();

        try {
            $newAssignment = $this->inventoryService->replaceItem(
                $assignment,
                $replacementItem,
                Auth::user(),
                $validated,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.inventory.assignments.show', $newAssignment)
            ->with('success', 'Device replaced successfully.');
    }

    protected function filteredAssignmentsQuery(Request $request, int $ispId): Builder
    {
        return InventoryAssignment::forIsp($ispId)
            ->with(['item', 'customer', 'router', 'technician'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($w) use ($term) {
                    $w->whereHas('item', fn ($iq) => $iq->where('serial_number', 'like', $term)
                        ->orWhere('name', 'like', $term))
                        ->orWhereHas('customer', fn ($cq) => $cq->where('full_name', 'like', $term)
                            ->orWhere('phone_number', 'like', $term));
                });
            })
            ->when($request->filled('installation_status'), fn ($q) => $q->where('installation_status', $request->installation_status))
            ->when($request->has('active') && $request->active !== '', fn ($q) => $q->where('is_active', (bool) $request->active))
            ->latest('assigned_at');
    }

    protected function authorizeAssignment(InventoryAssignment $assignment): void
    {
        if ($assignment->isp_id !== Auth::user()->isp_id) {
            abort(404);
        }
    }
}
