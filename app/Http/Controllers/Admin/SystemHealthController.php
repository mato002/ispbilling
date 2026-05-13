<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $statusFilter = $request->string('status')->toString();

        $query = Router::query()
            ->with(['isp:id,name'])
            ->orderByDesc('reachability_status')
            ->orderBy('isp_id')
            ->orderBy('name');

        if ($statusFilter !== '') {
            $query->where('reachability_status', $statusFilter);
        }

        $routers = $query->paginate(40)->withQueryString();

        $summary = Router::query()
            ->selectRaw('reachability_status, COUNT(*) as c')
            ->groupBy('reachability_status')
            ->pluck('c', 'reachability_status');

        return view('admin.system-health.index', [
            'routers' => $routers,
            'summary' => $summary,
            'statusFilter' => $statusFilter,
        ]);
    }
}
