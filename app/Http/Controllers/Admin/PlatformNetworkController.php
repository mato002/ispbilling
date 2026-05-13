<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PropagateRouterScriptJob;
use App\Models\Router;
use App\Models\RouterMetric;
use App\Support\IpPoolOverlapReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformNetworkController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $overlap = IpPoolOverlapReport::analyze();

        $routers = Router::query()
            ->with(['isp:id,name'])
            ->orderBy('isp_id')
            ->orderBy('name')
            ->get([
                'id', 'isp_id', 'name', 'ip_address', 'vendor', 'model', 'is_active',
                'reachability_status', 'poll_failure_count', 'last_poll_error',
            ]);

        $hours = min(72, max(6, (int) $request->get('traffic_hours', 24)));
        $traffic = RouterMetric::query()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->select(
                DB::raw("DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00') as bucket"),
                DB::raw('MAX(rx_bytes_per_sec + tx_bytes_per_sec) as peak_bps'),
                DB::raw('AVG(rx_bytes_per_sec + tx_bytes_per_sec) as avg_bps')
            )
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $maxPeak = (float) ($traffic->max('peak_bps') ?: 1);

        return view('admin.platform-network.index', [
            'overlap' => $overlap,
            'routers' => $routers,
            'traffic' => $traffic,
            'maxPeak' => $maxPeak,
            'trafficHours' => $hours,
        ]);
    }

    public function dispatchMaintenance(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'script_name' => ['required', 'string', 'max:64'],
            'script_body' => ['required', 'string', 'max:65535'],
            'operation' => ['required', 'string', 'max:32'],
            'vendor' => ['nullable', 'string', 'max:64'],
            'execute' => ['nullable', 'boolean'],
            'isp_ids_text' => ['nullable', 'string', 'max:500'],
        ]);

        $ispIds = [];
        if (! empty($validated['isp_ids_text'])) {
            $ispIds = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $validated['isp_ids_text']))));
        }

        $query = Router::query()->where('is_active', true);
        if (! empty($validated['vendor'])) {
            $query->where('vendor', 'like', '%'.$validated['vendor'].'%');
        }
        if (! empty($ispIds)) {
            $query->whereIn('isp_id', $ispIds);
        }

        $count = 0;
        $query->chunkById(100, function ($chunk) use ($validated, $request, &$count) {
            foreach ($chunk as $router) {
                PropagateRouterScriptJob::dispatch(
                    $router->id,
                    $validated['script_name'],
                    $validated['script_body'],
                    $validated['operation'],
                    (int) $request->user()->id,
                    $request->boolean('execute')
                );
                $count++;
            }
        });

        return redirect()
            ->route('admin.network.index')
            ->with('success', "Queued {$count} router job(s). Check each ISP's router action log for results.");
    }

    public function resetRouterCircuit(Request $request, Router $router): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $router->update([
            'poll_failure_count' => 0,
            'reachability_status' => 'offline',
            'last_poll_error' => 'Circuit reset by super admin — polling will resume.',
        ]);

        return redirect()
            ->route('admin.network.index')
            ->with('success', "Circuit cleared for {$router->name}. Health polls and reconcile jobs will target this NAS again.");
    }
}
