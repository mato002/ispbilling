<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRouterMigrationJob;
use App\Jobs\RollbackRouterMigrationJob;
use App\Models\Router;
use App\Models\RouterMigrationBatch;
use App\Models\RouterMigrationLog;
use App\Services\RouterMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouterMigrationController extends Controller
{
    public function index(Request $request): View
    {
        $ispId = (int) auth()->user()->isp_id;

        $batches = RouterMigrationBatch::query()
            ->forIsp($ispId)
            ->with(['oldRouter:id,name', 'newRouter:id,name', 'creator:id,name'])
            ->latest()
            ->paginate(20);

        return view('admin.network.router-migration.index', compact('batches'));
    }

    public function create(): View
    {
        $ispId = (int) auth()->user()->isp_id;
        $routers = Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name', 'ip_address']);

        return view('admin.network.router-migration.create', compact('routers'));
    }

    public function preview(Request $request, RouterMigrationService $service): JsonResponse
    {
        $ispId = (int) auth()->user()->isp_id;

        $validated = $request->validate([
            'old_router_id' => 'required|integer',
            'new_router_id' => 'required|integer|different:old_router_id',
            'eligibility' => 'nullable|in:active_only,all_on_router',
        ]);

        $eligibility = $validated['eligibility'] ?? RouterMigrationService::ELIGIBILITY_ACTIVE_ONLY;

        try {
            $service->validateBatch($ispId, (int) $validated['old_router_id'], (int) $validated['new_router_id']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $customers = $service->affectedCustomersQuery($ispId, (int) $validated['old_router_id'], $eligibility)
            ->orderBy('full_name')
            ->limit(100)
            ->get(['id', 'full_name', 'username', 'connection_type', 'status']);

        $total = $service->affectedCustomersQuery($ispId, (int) $validated['old_router_id'], $eligibility)->count();

        return response()->json([
            'total' => $total,
            'eligibility' => $eligibility,
            'preview' => $customers->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'username' => $c->username,
                'connection_type' => $c->connection_type,
                'status' => $c->status,
                'plan' => $c->subscriptions->first()?->plan?->name,
            ]),
        ]);
    }

    public function store(Request $request, RouterMigrationService $service): RedirectResponse
    {
        $ispId = (int) auth()->user()->isp_id;

        $validated = $request->validate([
            'old_router_id' => 'required|integer',
            'new_router_id' => 'required|integer|different:old_router_id',
            'eligibility' => 'nullable|in:active_only,all_on_router',
            'confirm' => 'accepted',
        ]);

        try {
            $batch = $service->createBatch(
                $ispId,
                (int) $validated['old_router_id'],
                (int) $validated['new_router_id'],
                (int) auth()->id(),
                $validated['eligibility'] ?? RouterMigrationService::ELIGIBILITY_ACTIVE_ONLY,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($batch->total_customers === 0) {
            $batch->update([
                'status' => RouterMigrationBatch::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('isp.router-migration.show', $batch)
                ->with('success', 'No eligible subscribers found on the source router.');
        }

        ProcessRouterMigrationJob::dispatch($batch->id);

        return redirect()
            ->route('isp.router-migration.show', $batch)
            ->with('success', 'Migration batch queued. Processing will run in the background.');
    }

    public function show(RouterMigrationBatch $batch): View
    {
        $this->authorizeBatch($batch);

        $batch->load(['oldRouter', 'newRouter', 'creator']);

        $recentLogs = RouterMigrationLog::query()
            ->where('batch_id', $batch->id)
            ->with('customer:id,full_name,username')
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.network.router-migration.show', compact('batch', 'recentLogs'));
    }

    public function status(RouterMigrationBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        $batch->refresh();

        $logs = RouterMigrationLog::query()
            ->where('batch_id', $batch->id)
            ->with('customer:id,full_name')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'customer' => $log->customer?->full_name,
                'action' => $log->action,
                'status' => $log->status,
                'error' => $log->error_message,
                'at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'batch' => [
                'id' => $batch->id,
                'status' => $batch->status,
                'total_customers' => $batch->total_customers,
                'processed_customers' => $batch->processed_customers,
                'success_count' => $batch->success_count,
                'failed_count' => $batch->failed_count,
                'progress_percent' => $batch->progressPercent(),
                'is_terminal' => $batch->isTerminal(),
            ],
            'logs' => $logs,
        ]);
    }

    public function rollback(RouterMigrationBatch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        if (! $batch->canRollback()) {
            return back()->with('error', 'This batch cannot be rolled back.');
        }

        RollbackRouterMigrationJob::dispatch($batch->id);

        return back()->with('success', 'Rollback queued. Customers will be restored on the previous router in the background.');
    }

    protected function authorizeBatch(RouterMigrationBatch $batch): void
    {
        if ($batch->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
    }
}
