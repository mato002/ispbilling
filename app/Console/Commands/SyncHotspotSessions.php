<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Models\HotspotSession;
use App\Models\CustomerUsageStat;
use App\Services\DataUsageRecordingService;

class SyncHotspotSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotspot:sync-sessions {--router-id= : Sync specific router}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active hotspot sessions from MikroTik routers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routerId = $this->option('router-id');

        if ($routerId) {
            $routers = Router::where('id', $routerId)->where('is_active', true)->get();
        } else {
            $routers = Router::where('is_active', true)->get();
        }

        if ($routers->isEmpty()) {
            $this->error('No active routers found.');
            return 1;
        }

        $this->info("Syncing sessions for {$routers->count()} router(s)...");

        $totalSynced = 0;
        foreach ($routers as $router) {
            try {
                $this->info("Syncing router: {$router->name} ({$router->ip_address})");
                
                $mikrotik = new MikroTikService($router);
                $synced = $mikrotik->syncActiveSessions();
                
                $totalSynced += $synced;
                $this->info("  ✓ Synced {$synced} session(s)");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
            }
        }

        // Update usage stats for ended sessions
        $this->updateUsageStats();

        $recorded = app(DataUsageRecordingService::class);
        Router::where('is_active', true)
            ->when($routerId, fn ($q) => $q->where('id', $routerId))
            ->each(fn (Router $router) => $recorded->finalizeEndedHotspotSessions($router));

        $this->info("\nTotal sessions synced: {$totalSynced}");
        return 0;
    }

    /**
     * Update usage stats for ended sessions
     */
    protected function updateUsageStats()
    {
        $endedSessions = HotspotSession::where('status', 'ended')
            ->whereNull('ended_at')
            ->orWhere(function ($query) {
                $query->where('status', 'ended')
                      ->whereDate('ended_at', today());
            })
            ->with('customer')
            ->get();

        foreach ($endedSessions as $session) {
            if (!$session->customer_id) {
                continue;
            }

            $date = $session->ended_at ? $session->ended_at->toDateString() : today()->toDateString();
            $subscriptionId = $session->customer?->activeSubscription()?->id ?? null;

            CustomerUsageStat::updateOrCreate(
                [
                    'customer_id' => $session->customer_id,
                    'date' => $date,
                ],
                [
                    'subscription_id' => $subscriptionId,
                    'bytes_uploaded' => \DB::raw('COALESCE(bytes_uploaded, 0) + ' . $session->bytes_uploaded),
                    'bytes_downloaded' => \DB::raw('COALESCE(bytes_downloaded, 0) + ' . $session->bytes_downloaded),
                    'total_bytes' => \DB::raw('COALESCE(total_bytes, 0) + ' . $session->total_bytes),
                    'session_count' => \DB::raw('COALESCE(session_count, 0) + 1'),
                    'total_uptime_seconds' => \DB::raw('COALESCE(total_uptime_seconds, 0) + ' . $session->uptime_seconds),
                ]
            );
        }
    }
}





