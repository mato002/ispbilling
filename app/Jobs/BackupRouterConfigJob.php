<?php

namespace App\Jobs;

use App\Models\Router;
use App\Models\RouterActionLog;
use App\Models\RouterBackup;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Pulls /export from RouterOS and stores it on the configured disk.
 * Creates a `router_backups` row + a `RouterActionLog` entry.
 */
class BackupRouterConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 40;

    public function __construct(public int $routerId, public ?int $userId = null) {}

    public function handle(): void
    {
        $router = Router::find($this->routerId);
        if (! $router) {
            return;
        }

        $service = new MikroTikService($router);
        $config = $service->exportConfig();

        if ($config === null || trim($config) === '') {
            RouterActionLog::log([
                'isp_id' => $router->isp_id,
                'router_id' => $router->id,
                'user_id' => $this->userId,
                'action' => 'backup_router_config',
                'status' => 'failed',
                'description' => "Backup failed for {$router->name}: empty export",
            ]);

            return;
        }

        $disk = config('filesystems.default', 'local');
        $filename = sprintf(
            'router-%d-%s.rsc',
            $router->id,
            now()->format('Ymd-His')
        );
        $path = "router-backups/{$router->isp_id}/{$router->id}/{$filename}";

        Storage::disk($disk)->put($path, $config);

        $size = strlen($config);
        $checksum = hash('sha256', $config);

        RouterBackup::create([
            'router_id' => $router->id,
            'isp_id' => $router->isp_id,
            'created_by_user_id' => $this->userId,
            'type' => 'export',
            'storage_disk' => $disk,
            'storage_path' => $path,
            'filename' => $filename,
            'size_bytes' => $size,
            'checksum_sha256' => $checksum,
            'status' => 'completed',
        ]);

        RouterActionLog::log([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'user_id' => $this->userId,
            'action' => 'backup_router_config',
            'status' => 'success',
            'description' => "Backup stored ({$filename}, ".number_format($size).' bytes)',
        ]);
    }
}
