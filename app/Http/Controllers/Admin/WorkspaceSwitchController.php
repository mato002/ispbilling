<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ISP;
use App\Models\User;
use App\Support\SuperAdminWorkspaceContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceSwitchController extends Controller
{
    public function enter(Request $request, ISP $isp): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        abort_if($request->session()->has(SuperAdminWorkspaceContext::SESSION_KEY), 409, 'End the current ISP admin session before switching to another ISP.');

        $ispAdmin = User::withoutGlobalScopes()
            ->where('isp_id', $isp->id)
            ->where('role', 'isp_admin')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if (! $ispAdmin) {
            return back()->with('error', 'This ISP has no ISP admin account to switch into.');
        }

        $superAdmin = $request->user();
        $request->session()->put(SuperAdminWorkspaceContext::SESSION_KEY, [
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $ispAdmin->id,
            'isp_id' => $isp->id,
        ]);
        $this->audit($request, $isp, $superAdmin->id, 'super_admin_impersonation_started', 'Super Admin started an ISP admin troubleshooting session.', $ispAdmin);
        Auth::login($ispAdmin);
        $request->session()->regenerate(true);

        return redirect()->route('isp.dashboard')->with('success', 'You are now authenticated as '.$ispAdmin->name.' (ISP admin) for '.$isp->name.'.');
    }

    public function exit(Request $request): RedirectResponse
    {
        abort_unless(SuperAdminWorkspaceContext::active($request), 403);

        $isp = SuperAdminWorkspaceContext::isp($request);
        $superAdminId = SuperAdminWorkspaceContext::superAdminId($request);
        $superAdmin = $superAdminId ? User::withoutGlobalScopes()->find($superAdminId) : null;

        abort_unless($superAdmin?->isSuperAdmin(), 403);

        if ($isp && $superAdminId) {
            $this->audit($request, $isp, $superAdminId, 'super_admin_impersonation_ended', 'Super Admin ended the ISP admin troubleshooting session.', $request->user());
        }

        $request->session()->forget(SuperAdminWorkspaceContext::SESSION_KEY);
        Auth::login($superAdmin);
        $request->session()->regenerate(true);

        return redirect()->route('admin.dashboard')->with('success', 'Returned to your Super Admin account.');
    }

    private function audit(Request $request, ISP $isp, int $actorId, string $action, string $description, User $target): void
    {
        ActivityLog::withoutGlobalScope('isp_tenant')->create([
            'isp_id' => $isp->id,
            'user_id' => $actorId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'metadata' => ['actor_role' => 'super_admin', 'target_user_id' => $target->id, 'target_email' => $target->email],
        ]);
    }
}
