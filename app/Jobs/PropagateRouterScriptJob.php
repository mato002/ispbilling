<?php

namespace App\Jobs;

use App\Models\Router;
use App\Models\RouterActionLog;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Validates NAS connectivity and records a global maintenance payload per router.
 * Extend {@see MikroTikService} with a dedicated RouterOS script runner when you are ready to apply payloads.
 */
class PropagateRouterScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $routerId,
        public string $scriptName,
        public string $scriptBody,
        public string $operation,
        public int $initiatedByUserId,
        public bool $execute = false
    ) {}

    public function handle(): void
    {
        $router = Router::query()->with('isp')->find($this->routerId);
        if (! $router) {
            return;
        }

        $description = 'Global controller: '.$this->operation.' — '.$this->scriptName;

        try {
            $mik = new MikroTikService($router);
            if (! $mik->connect()) {
                RouterActionLog::log([
                    'isp_id' => $router->isp_id,
                    'router_id' => $router->id,
                    'customer_id' => null,
                    'user_id' => $this->initiatedByUserId,
                    'action' => 'global_nas_maintenance',
                    'status' => 'failed',
                    'description' => $description,
                    'error_message' => 'RouterOS API not reachable or credentials rejected',
                    'request_data' => [
                        'script_name' => $this->scriptName,
                        'operation' => $this->operation,
                        'execute' => $this->execute,
                        'payload_preview' => mb_substr($this->scriptBody, 0, 500),
                    ],
                ]);

                return;
            }

            RouterActionLog::log([
                'isp_id' => $router->isp_id,
                'router_id' => $router->id,
                'customer_id' => null,
                'user_id' => $this->initiatedByUserId,
                'action' => 'global_nas_maintenance',
                'status' => 'success',
                'description' => $this->execute
                    ? $description.' — API session OK; payload logged for operator review.'
                    : $description.' — dry run (connectivity only).',
                'request_data' => [
                    'script_name' => $this->scriptName,
                    'operation' => $this->operation,
                    'execute' => $this->execute,
                    'payload_preview' => mb_substr($this->scriptBody, 0, 2000),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('PropagateRouterScriptJob failed', ['router_id' => $this->routerId, 'e' => $e->getMessage()]);
            RouterActionLog::log([
                'isp_id' => $router->isp_id,
                'router_id' => $router->id,
                'customer_id' => null,
                'user_id' => $this->initiatedByUserId,
                'action' => 'global_nas_maintenance',
                'status' => 'failed',
                'description' => $description,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
