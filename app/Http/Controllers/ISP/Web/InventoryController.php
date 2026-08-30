<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\InventoryCategoryThreshold;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventoryTechnicianAssignment;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryQrCodeService;
use App\Services\InventoryService;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class InventoryController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected InventoryService $inventoryService,
        protected InventoryQrCodeService $qrCodeService,
    ) {}

    public function index(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $query = $this->filteredItemsQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $items = $query->paginate($perPage)->withQueryString();

        $items->getCollection()->each(function (InventoryItem $item) {
            if (! $item->qr_code_path) {
                $this->qrCodeService->ensureGenerated($item);
            }
        });

        $stockCounts = $this->inventoryService->stockCounts($ispId);
        $lowStockAlerts = $this->inventoryService->lowStockAlerts($ispId);

        $filterChips = $this->listingFilterChips($request, route('isp.inventory.index'), [
            'q' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => InventoryCatalog::STATUSES,
            ],
            'category' => [
                'label' => 'Category',
                'values' => InventoryCatalog::CATEGORIES,
            ],
            'location' => [
                'label' => 'Location',
                'values' => InventoryCatalog::LOCATIONS,
            ],
        ]);

        return view('isp.inventory.index', compact(
            'items',
            'stockCounts',
            'lowStockAlerts',
            'filterChips',
        ));
    }

    public function create(): View
    {
        $purchases = InventoryPurchase::forIsp(Auth::user()->isp_id)
            ->latest('purchase_date')
            ->limit(50)
            ->get(['id', 'vendor_name', 'invoice_number', 'purchase_date']);

        return view('isp.inventory.create', [
            'purchases' => $purchases,
            'catalog' => InventoryCatalog::class,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ispId = Auth::user()->isp_id;

        $validated = $this->validateItem($request, $ispId);

        $item = $this->inventoryService->createItem($ispId, $validated, Auth::user());

        return redirect()
            ->route('isp.inventory.show', $item)
            ->with('success', 'Inventory item added.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorizeItem($inventoryItem);

        $this->qrCodeService->ensureGenerated($inventoryItem);
        $inventoryItem->refresh();

        $inventoryItem->load([
            'activeAssignment.customer',
            'activeAssignment.router',
            'activeAssignment.technician',
            'activeTechnicianAssignment.technician',
            'purchase',
            'assignments' => fn ($q) => $q->with(['customer', 'technician'])->latest('assigned_at')->limit(10),
            'transactions' => fn ($q) => $q->with(['performedBy', 'customer'])->latest('created_at')->limit(15),
        ]);

        return view('isp.inventory.show', [
            'item' => $inventoryItem,
            'catalog' => InventoryCatalog::class,
            'technicians' => User::where('isp_id', $inventoryItem->isp_id)
                ->where('role', 'isp_technician')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function edit(InventoryItem $inventoryItem): View|RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        if ($inventoryItem->status === InventoryItem::STATUS_ASSIGNED) {
            return redirect()
                ->route('isp.inventory.show', $inventoryItem)
                ->with('error', 'Return the device before editing while it is assigned.');
        }

        $purchases = InventoryPurchase::forIsp(Auth::user()->isp_id)
            ->latest('purchase_date')
            ->limit(50)
            ->get(['id', 'vendor_name', 'invoice_number', 'purchase_date']);

        return view('isp.inventory.edit', [
            'item' => $inventoryItem,
            'purchases' => $purchases,
            'catalog' => InventoryCatalog::class,
        ]);
    }

    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        if ($inventoryItem->status === InventoryItem::STATUS_ASSIGNED) {
            return back()->with('error', 'Return the device before editing while it is assigned.');
        }

        $validated = $this->validateItem($request, $inventoryItem->isp_id, $inventoryItem->id);

        $this->inventoryService->updateItem($inventoryItem, $validated);

        return redirect()
            ->route('isp.inventory.show', $inventoryItem)
            ->with('success', 'Inventory item updated.');
    }

    public function faulty(Request $request): View
    {
        $ispId = Auth::user()->isp_id;
        $request->merge(['status' => InventoryItem::STATUS_FAULTY]);

        $query = $this->filteredItemsQuery($request, $ispId)
            ->with(['assignments' => fn ($q) => $q->with('customer')->latest('assigned_at')->limit(1)]);
        $perPage = $this->resolvePerPage($request);
        $items = $query->paginate($perPage)->withQueryString();

        return view('isp.inventory.faulty', compact('items'));
    }

    public function markFaulty(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->markFaulty($inventoryItem, Auth::user(), $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.inventory.faulty')
            ->with('success', 'Device marked as faulty.');
    }

    public function returnToWarehouse(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        $assignment = $inventoryItem->activeAssignment;
        if (! $assignment) {
            return back()->with('error', 'No active assignment to return.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->returnItem($assignment, Auth::user(), $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Device returned to warehouse.');
    }

    public function retire(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->retireItem($inventoryItem, Auth::user(), $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.inventory.show', $inventoryItem)
            ->with('success', 'Device retired from inventory.');
    }

    public function transactions(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $query = InventoryTransaction::forIsp($ispId)
            ->with(['item', 'performedBy', 'customer', 'relatedItem'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->whereHas('item', fn ($iq) => $iq->where('serial_number', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->latest('created_at');

        $perPage = $this->resolvePerPage($request);
        $transactions = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.inventory.transactions'), [
            'q' => 'Search',
            'action' => [
                'label' => 'Action',
                'values' => InventoryCatalog::TRANSACTION_ACTIONS,
            ],
        ]);

        return view('isp.inventory.transactions', compact('transactions', 'filterChips'));
    }

    public function purchases(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $purchases = InventoryPurchase::forIsp($ispId)
            ->withCount('items')
            ->with('createdBy')
            ->latest('purchase_date')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return view('isp.inventory.purchases.index', compact('purchases'));
    }

    public function createPurchase(): View
    {
        return view('isp.inventory.purchases.create');
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_name' => 'required|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'total_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $purchase = $this->inventoryService->createPurchase(
            Auth::user()->isp_id,
            $validated,
            Auth::user(),
        );

        return redirect()
            ->route('isp.inventory.purchases')
            ->with('success', 'Purchase record saved.');
    }

    public function thresholds(): View
    {
        $ispId = Auth::user()->isp_id;
        $stored = InventoryCategoryThreshold::forIsp($ispId)->get()->keyBy('category');

        $thresholds = collect(InventoryCatalog::CATEGORIES)->mapWithKeys(function ($label, $category) use ($stored) {
            return [$category => $stored->get($category)?->low_stock_threshold ?? 5];
        });

        return view('isp.inventory.thresholds', compact('thresholds'));
    }

    public function scan(): View
    {
        $isTechnician = request()->routeIs('isp.technician.*');

        return view('isp.inventory.scan', [
            'resolveUrl' => $isTechnician
                ? route('isp.technician.inventory.scan.resolve')
                : route('isp.inventory.scan.resolve'),
            'backUrl' => $isTechnician
                ? route('isp.technician.inventory.index')
                : route('isp.inventory.index'),
        ]);
    }

    public function resolveScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payload' => 'required|string|max:5000',
        ]);

        $itemId = $this->qrCodeService->parseInventoryItemId($validated['payload']);
        if (! $itemId) {
            return response()->json(['message' => 'Unrecognized QR code format.'], 422);
        }

        $item = InventoryItem::forIsp(Auth::user()->isp_id)->whereKey($itemId)->first();
        if (! $item) {
            return response()->json(['message' => 'Device not found in your workspace.'], 404);
        }

        $user = Auth::user();
        if ($user->isTechnician()) {
            $hold = InventoryTechnicianAssignment::forIsp($user->isp_id)
                ->forTechnician($user->id)
                ->active()
                ->where('inventory_item_id', $item->id)
                ->first();

            if (! $hold) {
                return response()->json(['message' => 'This device is not assigned to you.'], 403);
            }

            return response()->json([
                'redirect' => route('isp.technician.inventory.show', $hold),
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'serial_number' => $item->serial_number,
                ],
            ]);
        }

        return response()->json([
            'redirect' => route('isp.inventory.show', $item),
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'serial_number' => $item->serial_number,
            ],
        ]);
    }

    public function updateThresholds(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thresholds' => 'required|array',
            'thresholds.*' => 'integer|min:0|max:10000',
        ]);

        $this->inventoryService->syncCategoryThresholds(Auth::user()->isp_id, $validated['thresholds']);

        return back()->with('success', 'Low stock thresholds updated.');
    }

    public function technicianAssignments(Request $request): View
    {
        $ispId = Auth::user()->isp_id;

        $assignments = InventoryTechnicianAssignment::forIsp($ispId)
            ->with(['item', 'technician', 'assignedBy'])
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->latest('assigned_at')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return view('isp.inventory.technician-assignments.index', compact('assignments'));
    }

    public function assignTechnician(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'technician_user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) use ($inventoryItem) {
                    $query->where('isp_id', $inventoryItem->isp_id)->where('role', 'isp_technician');
                }),
            ],
            'notes' => 'nullable|string|max:2000',
        ]);

        $technician = User::where('isp_id', $inventoryItem->isp_id)
            ->whereKey($validated['technician_user_id'])
            ->firstOrFail();

        try {
            $this->inventoryService->assignToTechnician(
                $inventoryItem,
                $technician,
                Auth::user(),
                $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Device issued to '.$technician->name.'.');
    }

    protected function filteredItemsQuery(Request $request, int $ispId): Builder
    {
        return InventoryItem::forIsp($ispId)
            ->with(['activeAssignment.customer'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('serial_number', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('mac_address', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('location'), fn ($q) => $q->where('location', $request->location))
            ->latest('id');
    }

    protected function validateItem(Request $request, int $ispId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_items', 'serial_number')
                    ->where('isp_id', $ispId)
                    ->ignore($ignoreId),
            ],
            'mac_address' => 'nullable|string|max:50',
            'category' => ['required', Rule::in(array_keys(InventoryCatalog::CATEGORIES))],
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
            'location' => ['nullable', Rule::in(array_keys(InventoryCatalog::LOCATIONS))],
            'inventory_purchase_id' => [
                'nullable',
                Rule::exists('inventory_purchases', 'id')->where('isp_id', $ispId),
            ],
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    protected function authorizeItem(InventoryItem $item): void
    {
        if ($item->isp_id !== Auth::user()->isp_id) {
            abort(404);
        }
    }
}
