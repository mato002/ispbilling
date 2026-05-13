<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ISP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('isp');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
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

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
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
            'role' => ['required', Rule::in(['super_admin', 'isp_admin', 'isp_staff'])],
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
            if (!$request->has('isp_id') || is_null($request->input('isp_id'))) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['isp_id' => 'ISP is required for ISP Admin and Staff roles.']);
            }
        }

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('isp');
        return view('admin.users.show', compact('user'));
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
            'role' => ['required', Rule::in(['super_admin', 'isp_admin', 'isp_staff'])],
            'isp_id' => ['nullable', Rule::exists(ISP::class, 'id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['is_active'] = $validated['status'] === 'active';
        unset($validated['status']);

        // If password is provided, hash it
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // If role is super_admin, isp_id should be null
        if ($validated['role'] === 'super_admin') {
            $validated['isp_id'] = null;
        } else {
            // For isp_admin and isp_staff roles, isp_id is required
            if (!$request->has('isp_id') || is_null($request->input('isp_id'))) {
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
        if ($user->role === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Only super admins can delete super admin accounts.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
