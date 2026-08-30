<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\User;
use App\Services\Admin\OperatorProfileDashboardService;
use App\Services\IspAdminOverviewService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request)
    {
        $query = $this->filteredUsersQuery($request);
        $perPage = $this->resolvePerPage($request);
        $users = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.users.index'), [
            'search' => 'Search',
            'role' => [
                'label' => 'Role',
                'values' => [
                    'super_admin' => 'Super Admin',
                    'isp_admin' => 'ISP Admin',
                    'isp_staff' => 'Staff',
                ],
            ],
            'status' => [
                'label' => 'Status',
                'values' => ['active' => 'Active', 'inactive' => 'Inactive'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.users.export.csv'));

        return view('admin.users.index', compact('users', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = $this->filteredUsersQuery($request);

        return CsvExporter::download(
            'users-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Name', 'Email', 'Phone', 'Role', 'ISP', 'Status', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($users) use ($out) {
                    foreach ($users as $user) {
                        fputcsv($out, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->phone,
                            $user->role,
                            $user->isp?->name,
                            $user->is_active ? 'Active' : 'Inactive',
                            $user->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredUsersQuery(Request $request)
    {
        $query = User::with('isp');

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern)
                    ->orWhere('phone', 'like', $pattern);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->latest();
    }

    public function create()
    {
        $isps = ISP::all();

        return view('admin.users.create', compact('isps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['super_admin', 'isp_admin', 'isp_staff', 'isp_technician'])],
            'isp_id' => ['nullable', Rule::exists(ISP::class, 'id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['status'] === 'active';
        unset($validated['status']);

        // If role is super_admin, isp_id should be null
        if ($validated['role'] === 'super_admin') {
            $validated['isp_id'] = null;
        } else {
            // For isp_admin and isp_staff roles, isp_id is required
            if (! $request->has('isp_id') || is_null($request->input('isp_id'))) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['isp_id' => 'ISP is required for ISP Admin and Staff roles.']);
            }
        }

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(
        User $user,
        OperatorProfileDashboardService $operatorDashboardService,
        IspAdminOverviewService $tenantOverviewService,
    ) {
        $user->load('isp');
        $operatorDashboard = $operatorDashboardService->snapshot($user);

        $tenantOverview = ($user->isp_id && $user->isp)
            ? $tenantOverviewService->build($user->isp)
            : null;

        return view('admin.users.show', compact('user', 'operatorDashboard', 'tenantOverview'));
    }

    public function edit(User $user)
    {
        $isps = ISP::all();

        return view('admin.users.edit', compact('user', 'isps'));
    }

    public function update(Request $request, User $user)
    {
        // Quick activate/deactivate from user detail sidebar (only sends status)
        if (! $request->filled('name') && $request->filled('status')) {
            $request->validate([
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $user->update([
                'is_active' => $request->input('status') === 'active',
            ]);

            return redirect()->back()
                ->with('success', 'User status updated.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['super_admin', 'isp_admin', 'isp_staff', 'isp_technician'])],
            'isp_id' => ['nullable', Rule::exists(ISP::class, 'id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['is_active'] = $validated['status'] === 'active';
        unset($validated['status']);

        // If password is provided, hash it
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // If role is super_admin, isp_id should be null
        if ($validated['role'] === 'super_admin') {
            $validated['isp_id'] = null;
        } else {
            // For isp_admin and isp_staff roles, isp_id is required
            if (! $request->has('isp_id') || is_null($request->input('isp_id'))) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['isp_id' => 'ISP is required for ISP Admin and Staff roles.']);
            }
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deletion of the currently authenticated user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deletion of super admin if not super admin
        if ($user->role === 'super_admin' && ! auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Only super admins can delete super admin accounts.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
