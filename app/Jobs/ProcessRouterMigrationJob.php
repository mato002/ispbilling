<?php

namespace App\Jobs;

use App\Models\RouterMigrationBatch;
use App\Services\NotificationService;
use App\Services\RouterMigrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessRouterMigrationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $batchId) {}

    public function handle(
        RouterMigrationService $migrationService,
        NotificationService $notifications,
    ): void {
        $batch = RouterMigrationBatch::query()
            ->with(['oldRouter', 'newRouter'])
            ->find($this->batchId);

        if (! $batch || $batch->isTerminal()) {
            return;
        }

        if ($batch->status === RouterMigrationBatch::STATUS_PENDING) {
            $batch->update([
                'status' => RouterMigrationBatch::STATUS_RUNNING,
                'started_at' => $batch->started_at ?? now(),
            ]);
        }

        $chunkSize = config('router_migration.batch_size', 25);
        $customers = $migrationService->nextCustomerChunk($batch, $chunkSize);

        if ($customers === []) {
            $this->finalize($batch, $notifications);

            return;
        }

        foreach ($customers as $customer) {
            $ok = $migrationService->migrateCustomer($batch, $customer);

            $batch->increment('processed_customers');
            $batch->increment($ok ? 'success_count' : 'failed_count');

            usleep(config('router_migration.api_delay_ms', 300) * 1000);
        }

        $batch->refresh();

        if ($batch->processed_customers >= $batch->total_customers) {
            $this->finalize($batch, $notifications);

            return;
        }

        self::dispatch($batch->id)
            ->delay(now()->addSeconds(config('router_migration.chunk_redispatch_delay_seconds', 5)));
    }

    protected function finalize(RouterMigrationBatch $batch, NotificationService $notifications): void
    {
        $batch->refresh();

        if ($batch->isTerminal()) {
            return;
        }

        $status = $batch->failed_count > 0 && $batch->success_count === 0
            ? RouterMigrationBatch::STATUS_FAILED
            : RouterMigrationBatch::STATUS_COMPLETED;

        $batch->update([
            'status' => $status,
            'completed_at' => now(),
        ]);

        $batch->load(['oldRouter', 'newRouter']);

        try {
            $notifications->routerMigrationFinished($batch->fresh(['oldRouter', 'newRouter']));
        } catch (\Throwable $e) {
            Log::warning('Router migration completion notification failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRouterMigrationJob failed', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);

        RouterMigrationBatch::query()
            ->whereKey($this->batchId)
            ->where('status', RouterMigrationBatch::STATUS_RUNNING)
            ->update([
                'status' => RouterMigrationBatch::STATUS_FAILED,
                'completed_at' => now(),
            ]);
    }
}
