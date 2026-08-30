<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IspSettingsChangeTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class IspWorkspaceUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (! $user || ! $user->isIspAdmin()) {
                abort(403, 'Only ISP administrators can manage workspace users.');
            }

            return $next($request);
        });
    }

    public function create()
    {
        return view('isp.settings.users.create');
    }

    public function store(Request $request)
    {
        $isp = auth()->user()->isp;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:isp_admin,isp_staff,isp_technician',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'isp_id' => $isp->id,
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        IspSettingsChangeTracker::touch($isp->id);

        return redirect()->route('isp.settings.users')->with('success', 'Workspace user created successfully.');
    }

    public function edit(User $user)
    {
        $this->assertSameWorkspace($user);

        return view('isp.settings.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertSameWorkspace($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:isp_admin,isp_staff,isp_technician',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'isp_admin') {
            return back()->withErrors(['role' => 'You cannot demote your own administrator account.']);
        }

        if ($user->id === auth()->id() && ! $request->boolean('is_active', true)) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        IspSettingsChangeTracker::touch(auth()->user()->isp_id);

        return redirect()->route('isp.settings.users')->with('success', 'Workspace user updated successfully.');
    }

    protected function assertSameWorkspace(User $user): void
    {
        $ispId = auth()->user()->isp_id;

        if ($user->isp_id !== $ispId || $user->isSuperAdmin()) {
            abort(404);
        }
    }
}
