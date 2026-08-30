<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Router;
use App\Models\RouterBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches MikroTik config backups for ISPs with auto_backup_enabled.
 */
class DispatchScheduledRouterBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function handle(): void
    {
        ISP::query()
            ->where('is_active', true)
            ->select('id', 'settings')
            ->chunkById(50, function ($isps) {
                foreach ($isps as $isp) {
                    $this->processIsp($isp);
                }
            });
    }

    protected function processIsp(ISP $isp): void
    {
        $backups = data_get($isp->settings, 'backups', []);
        if (empty($backups['auto_backup_enabled'])) {
            return;
        }

        $frequency = $backups['backup_frequency'] ?? 'weekly';
        $retentionDays = max(1, (int) ($backups['retention_days'] ?? 30));

        $dueAfter = match ($frequency) {
            'daily' => now()->subDay(),
            'monthly' => now()->subMonth(),
            default => now()->subWeek(),
        };

        Router::query()
            ->where('isp_id', $isp->id)
            ->where('is_active', true)
            ->select('id')
            ->chunkById(100, function ($routers) use ($dueAfter, $retentionDays) {
                foreach ($routers as $router) {
                    $last = RouterBackup::query()
                        ->where('router_id', $router->id)
                        ->latest('id')
                        ->value('created_at');

                    if ($last && $last->greaterThan($dueAfter)) {
                        continue;
                    }

                    BackupRouterConfigJob::dispatch($router->id);
                    $this->pruneOldBackups($router->id, $retentionDays);
                }
            });

        $settings = $isp->settings ?? [];
        $settings['backups'] = array_merge($settings['backups'] ?? [], [
            'last_auto_router_backup_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);
    }

    protected function pruneOldBackups(int $routerId, int $retentionDays): void
    {
        try {
            RouterBackup::query()
                ->where('router_id', $routerId)
                ->where('created_at', '<', now()->subDays($retentionDays))
                ->orderBy('id')
                ->limit(50)
                ->get()
                ->each(function (RouterBackup $backup) {
                    $disk = $backup->storage_disk ?: config('filesystems.default', 'local');
                    if ($backup->storage_path && \Storage::disk($disk)->exists($backup->storage_path)) {
                        \Storage::disk($disk)->delete($backup->storage_path);
                    }
                    $backup->delete();
                });
        } catch (\Throwable $e) {
            Log::warning('Router backup prune failed', [
                'router_id' => $routerId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
