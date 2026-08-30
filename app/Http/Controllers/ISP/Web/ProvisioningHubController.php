<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Jobs\FlushSessionRebuildQueuesJob;
use App\Jobs\RefreshProvisioningSnapshotJob;
use App\Models\Customer;
use App\Models\NetworkAuditLog;
use App\Models\Router;
use App\Services\InventoryBindingService;
use App\Services\MetricsService;
use App\Services\ProvisioningSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvisioningHubController extends Controller
{
    public function index(
        Request $request,
        ProvisioningSnapshotService $snapshots,
        MetricsService $metrics,
    ): View {
        $ispId = (int) auth()->user()->isp_id;
        $payload = $snapshots->getPayload($ispId);
        $metricsData = $metrics->fromPayload($payload);

        $customerOptions = collect($payload['rows'] ?? [])
            ->map(fn (array $row) => [
                'id' => $row['customer_id'],
                'name' => $row['customer_name'],
                'router_id' => $row['router_id'],
            ])
            ->values();

        return view('admin.network.provisioning-hub', [
            'snapshots' => $payload['rows'] ?? [],
            'metrics' => $metricsData,
            'refreshedAt' => $payload['refreshed_at'] ?? $snapshots->refreshedAt($ispId),
            'ispName' => $payload['isp_name'] ?? '',
            'customerOptions' => $customerOptions,
            'routers' => Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $ispId = (int) auth()->user()->isp_id;
        RefreshProvisioningSnapshotJob::dispatch($ispId);

        return back()->with('success', 'Provisioning snapshot refresh queued.');
    }

    public function forceBindMac(Request $request, InventoryBindingService $bindingService): RedirectResponse
    {
        $ispId = (int) auth()->user()->isp_id;

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'new_mac_address' => 'required|string|max:32',
            'router_id' => 'required|integer',
        ]);

        Customer::query()
            ->forIsp($ispId)
            ->whereKey($validated['customer_id'])
            ->firstOrFail();

        try {
            $bindingService->forceBindMac(
                $ispId,
                (int) $validated['customer_id'],
                $validated['new_mac_address'],
                (int) $validated['router_id'],
                $request->user(),
                $request->ip(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'MAC authorization queued. Router sync will run in the background.');
    }

    public function flushQueues(Request $request): RedirectResponse
    {
        $ispId = (int) auth()->user()->isp_id;

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'router_id' => 'required|integer',
        ]);

        $customer = Customer::query()
            ->forIsp($ispId)
            ->whereKey($validated['customer_id'])
            ->firstOrFail();

        $router = Router::query()
            ->forIsp($ispId)
            ->whereKey($validated['router_id'])
            ->firstOrFail();

        $audit = NetworkAuditLog::create([
            'isp_id' => $ispId,
            'user_id' => $request->user()->id,
            'customer_id' => $customer->id,
            'router_id' => $router->id,
            'action' => NetworkAuditLog::ACTION_FLUSH_REBUILD_QUEUES,
            'status' => NetworkAuditLog::STATUS_QUEUED,
            'description' => 'Queued session flush and queue rebuild',
            'ip_address' => $request->ip(),
        ]);

        FlushSessionRebuildQueuesJob::dispatch($audit->id, $customer->id, $router->id);

        return back()->with('success', 'Session flush and queue rebuild queued.');
    }
}
