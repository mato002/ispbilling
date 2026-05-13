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

class RebootRouterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 20;

    public function __construct(public int $routerId, public ?int $userId = null) {}

    public function handle(): void
    {
        $router = Router::find($this->routerId);
        if (! $router) {
            return;
        }

        $service = new MikroTikService($router);
        $ok = $service->reboot();

        RouterActionLog::log([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'user_id' => $this->userId,
            'action' => 'reboot_router',
            'status' => $ok ? 'success' : 'failed',
            'description' => $ok
                ? "Reboot command sent to {$router->name}"
                : "Reboot command failed for {$router->name}",
        ]);

        if ($ok) {
            $router->update([
                'reachability_status' => 'rebooting',
                'last_poll_error' => 'Reboot in progress',
            ]);

            // Schedule a follow-up poll after the device should be back.
            PollRouterHealthJob::dispatch($router->id)->delay(now()->addSeconds(90));
        }
    }
}
