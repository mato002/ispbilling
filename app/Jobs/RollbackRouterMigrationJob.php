<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\RouterMigrationBatch;
use App\Models\RouterMigrationLog;
use App\Services\NotificationService;
use App\Services\RouterMigrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RollbackRouterMigrationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $batchId) {}

    public function handle(
        RouterMigrationService $migrationService,
        NotificationService $notifications,
    ): void {
        $batch = RouterMigrationBatch::query()
            ->with(['oldRouter', 'newRouter'])
            ->find($this->batchId);

        if (! $batch || ! $batch->canRollback()) {
            return;
        }

        $batch->update(['status' => RouterMigrationBatch::STATUS_RUNNING]);

        $completedLogs = RouterMigrationLog::query()
            ->where('batch_id', $batch->id)
            ->where('action', RouterMigrationLog::ACTION_COMPLETED)
            ->where('status', RouterMigrationLog::STATUS_SUCCESS)
            ->get();

        foreach ($completedLogs as $log) {
            $customer = Customer::query()->find($log->customer_id);
            if (! $customer) {
                continue;
            }

            $snapshot = $log->metadata ?? [];
            $migrationService->rollbackCustomer($batch, $customer, $snapshot);

            usleep(config('router_migration.api_delay_ms', 300) * 1000);
        }

        $batch->update([
            'status' => RouterMigrationBatch::STATUS_ROLLED_BACK,
            'completed_at' => now(),
            'metadata' => array_merge($batch->metadata ?? [], ['rolled_back_at' => now()->toIso8601String()]),
        ]);

        try {
            $notifications->routerMigrationFinished($batch->fresh(['oldRouter', 'newRouter']));
        } catch (\Throwable $e) {
            Log::warning('Router migration rollback notification failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RollbackRouterMigrationJob failed', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);
    }
}
