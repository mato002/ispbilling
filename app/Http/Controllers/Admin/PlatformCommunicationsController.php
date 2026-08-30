<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Services\Admin\PlatformSmsMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformCommunicationsController extends Controller
{
    public function index(PlatformSmsMasterService $smsMaster): View
    {
        $dashboard = $smsMaster->dashboard();

        return view('admin.platform-communications.index', [
            'metrics' => $dashboard['metrics'],
            'tenants' => $dashboard['tenants'],
            'recentDeposits' => $dashboard['recent_deposits'],
        ]);
    }

    public function toggleTenantSms(Request $request, ISP $isp, PlatformSmsMasterService $smsMaster): RedirectResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $smsMaster->setTenantSmsEnabled($isp, $request->boolean('enabled'));

        $state = $request->boolean('enabled') ? 'enabled' : 'paused';

        return redirect()
            ->route('admin.communications.index')
            ->with('success', "{$isp->name} SMS dispatch {$state}.");
    }
}
