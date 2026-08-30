<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryTechnicianAssignment;
use App\Models\Router;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class TechnicianInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function index(): View
    {
        $technician = Auth::user();

        $assignments = InventoryTechnicianAssignment::forIsp($technician->isp_id)
            ->forTechnician($technician->id)
            ->active()
            ->with('item')
            ->latest('assigned_at')
            ->get();

        return view('isp.technician.inventory.index', compact('assignments'));
    }

    public function show(InventoryTechnicianAssignment $technicianAssignment): View
    {
        $this->authorizeHold($technicianAssignment);

        $technicianAssignment->load('item');

        $customers = Customer::forIsp($technicianAssignment->isp_id)
            ->orderBy('full_name')
            ->limit(300)
            ->get(['id', 'full_name', 'phone_number', 'location', 'router_id']);

        $routers = Router::forIsp($technicianAssignment->isp_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'ip_address']);

        return view('isp.technician.inventory.show', compact('technicianAssignment', 'customers', 'routers'));
    }

    public function install(Request $request, InventoryTechnicianAssignment $technicianAssignment): RedirectResponse
    {
        $this->authorizeHold($technicianAssignment);

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('isp_id', $technicianAssignment->isp_id),
            ],
            'router_id' => [
                'nullable',
                Rule::exists('routers', 'id')->where('isp_id', $technicianAssignment->isp_id),
            ],
            'installation_location' => 'nullable|string|max:500',
            'installation_gps' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $customer = Customer::forIsp($technicianAssignment->isp_id)
            ->whereKey($validated['customer_id'])
            ->firstOrFail();

        try {
            $this->inventoryService->completeTechnicianInstallation(
                $technicianAssignment,
                $customer,
                $validated,
                Auth::user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.technician.inventory.index')
            ->with('success', 'Device installed and linked to '.$customer->full_name.'.');
    }

    public function markFaulty(Request $request, InventoryTechnicianAssignment $technicianAssignment): RedirectResponse
    {
        $this->authorizeHold($technicianAssignment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->markFaultyFromTechnicianHold(
                $technicianAssignment,
                Auth::user(),
                $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.technician.inventory.index')
            ->with('success', 'Device marked as faulty and returned to warehouse queue.');
    }

    public function returnToWarehouse(Request $request, InventoryTechnicianAssignment $technicianAssignment): RedirectResponse
    {
        $this->authorizeHold($technicianAssignment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->inventoryService->technicianReturnHold(
                $technicianAssignment,
                Auth::user(),
                $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.technician.inventory.index')
            ->with('success', 'Device returned to warehouse.');
    }

    protected function authorizeHold(InventoryTechnicianAssignment $hold): void
    {
        $user = Auth::user();

        if ($hold->isp_id !== $user->isp_id || ! $hold->is_active) {
            abort(404);
        }

        if ($hold->technician_user_id !== $user->id) {
            abort(403, 'This device is not assigned to you.');
        }
    }
}
