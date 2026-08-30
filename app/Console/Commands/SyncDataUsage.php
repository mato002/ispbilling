<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Router;
use App\Services\DataUsageRecordingService;
use App\Services\MikroTikService;
use Illuminate\Console\Command;

class SyncDataUsage extends Command
{
    protected $signature = 'usage:sync {--router-id= : Sync a specific router}';

    protected $description = 'Sync hotspot session usage records and poll PPPoE traffic from MikroTik routers';

    public function handle(DataUsageRecordingService $recorder): int
    {
        $routerId = $this->option('router-id');

        $routers = Router::query()
            ->where('is_active', true)
            ->when($routerId, fn ($q) => $q->where('id', $routerId))
            ->get();

        if ($routers->isEmpty()) {
            $this->error('No active routers found.');

            return 1;
        }

        $hotspotRecorded = 0;
        $pppoeRecorded = 0;

        foreach ($routers as $router) {
            $this->info("Syncing usage for router: {$router->name}");

            try {
                $mikrotik = new MikroTikService($router);
                $mikrotik->syncActiveSessions();
                $hotspotRecorded += $recorder->finalizeEndedHotspotSessions($router);

                if ($mikrotik->connect()) {
                    foreach ($mikrotik->getActivePppoeSessions() as $sessionRow) {
                        $username = $sessionRow['name'] ?? null;
                        if (! $username) {
                            continue;
                        }

                        $customer = Customer::query()
                            ->where('username', $username)
                            ->where('router_id', $router->id)
                            ->where('connection_type', 'pppoe')
                            ->first();

                        if (! $customer) {
                            continue;
                        }

                        if ($recorder->recordPppoeDelta($router, $sessionRow, $customer)) {
                            $pppoeRecorded++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $this->info("Hotspot session records: {$hotspotRecorded}");
        $this->info("PPPoE delta records: {$pppoeRecorded}");

        return 0;
    }
}
