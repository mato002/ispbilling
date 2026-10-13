<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DataUsage;
use App\Models\HotspotSession;
use App\Models\Router;
use Illuminate\Support\Carbon;

class DataUsageRecordingService
{
    /**
     * Persist a completed hotspot session as a usage record (idempotent per session).
     */
    public function recordHotspotSessionEnd(HotspotSession $session): ?DataUsage
    {
        if (! $session->customer_id) {
            return null;
        }

        $customer = $session->relationLoaded('customer')
            ? $session->customer
            : Customer::find($session->customer_id);

        $existing = DataUsage::query()->where('hotspot_session_id', $session->id)->first();
        $subscription = $existing?->subscription_id ? null : $this->subscriptionForSession($session);
        return DataUsage::updateOrCreate([
            'hotspot_session_id' => $session->id,
        ], [
            'isp_id' => $session->isp_id,
            'customer_id' => $session->customer_id,
            'package_id' => $existing?->package_id ?? $subscription?->plan_id,
            'subscription_id' => $existing?->subscription_id ?? $subscription?->id,
            'router_id' => $session->router_id,
            'upload_bytes' => $session->bytes_uploaded,
            'download_bytes' => $session->bytes_downloaded,
            'total_bytes' => $session->total_bytes,
            'recorded_at' => $session->ended_at ?? now(),
            'hotspot_session_id' => $session->id,
            'ip_address' => $session->ip_address,
            'mac_address' => $session->mac_address,
            'source' => DataUsage::SOURCE_HOTSPOT,
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
            'external_session_id' => $session->mikrotik_session_id,
        ]);
    }

    /**
     * Record incremental PPPoE usage from a router poll (delta since last snapshot).
     *
     * @param  array<string, mixed>  $sessionRow  MikroTik /ppp/active row
     */
    public function recordPppoeDelta(Router $router, array $sessionRow, Customer $customer): ?DataUsage
    {
        $upload = (int) ($sessionRow['bytes-up'] ?? $sessionRow['limit-bytes-in'] ?? 0);
        $download = (int) ($sessionRow['bytes-down'] ?? $sessionRow['limit-bytes-out'] ?? 0);
        $total = $upload + $download;

        if ($total <= 0) {
            return null;
        }

        $externalId = (string) ($sessionRow['.id'] ?? $sessionRow['session-id'] ?? '');
        $sessionKey = $externalId !== '' ? "pppoe:{$router->id}:{$externalId}" : "pppoe:{$router->id}:{$customer->id}";

        $alreadyUpload = (int) DataUsage::query()
            ->where('customer_id', $customer->id)
            ->where('router_id', $router->id)
            ->where('source', DataUsage::SOURCE_PPPOE)
            ->where('external_session_id', $sessionKey)
            ->sum('upload_bytes');

        $alreadyDownload = (int) DataUsage::query()
            ->where('customer_id', $customer->id)
            ->where('router_id', $router->id)
            ->where('source', DataUsage::SOURCE_PPPOE)
            ->where('external_session_id', $sessionKey)
            ->sum('download_bytes');

        $deltaUpload = max(0, $upload - $alreadyUpload);
        $deltaDownload = max(0, $download - $alreadyDownload);

        if ($deltaUpload === 0 && $deltaDownload === 0) {
            return null;
        }

        $packageId = $customer->activeSubscription()?->plan_id;
        $subscriptionId = $customer->activeSubscription()?->id;
        $now = now();

        return DataUsage::create([
            'isp_id' => $router->isp_id,
            'customer_id' => $customer->id,
            'package_id' => $packageId,
            'subscription_id' => $subscriptionId,
            'router_id' => $router->id,
            'upload_bytes' => $deltaUpload,
            'download_bytes' => $deltaDownload,
            'total_bytes' => $deltaUpload + $deltaDownload,
            'recorded_at' => $now,
            'ip_address' => $sessionRow['address'] ?? $sessionRow['caller-id'] ?? null,
            'mac_address' => $sessionRow['caller-id'] ?? null,
            'source' => DataUsage::SOURCE_PPPOE,
            'started_at' => $this->parseMikrotikUptimeStart($sessionRow['uptime'] ?? null, $now),
            'ended_at' => null,
            'external_session_id' => $sessionKey,
        ]);
    }

    /**
     * Generic hook for future RADIUS accounting or traffic-log ingestion.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordExternal(array $payload): DataUsage
    {
        $upload = (int) ($payload['upload_bytes'] ?? 0);
        $download = (int) ($payload['download_bytes'] ?? 0);
        $total = (int) ($payload['total_bytes'] ?? ($upload + $download));

        return DataUsage::create([
            'isp_id' => $payload['isp_id'],
            'customer_id' => $payload['customer_id'],
            'package_id' => $payload['package_id'] ?? null,
            'router_id' => $payload['router_id'] ?? null,
            'upload_bytes' => $upload,
            'download_bytes' => $download,
            'total_bytes' => $total,
            'recorded_at' => isset($payload['recorded_at'])
                ? Carbon::parse($payload['recorded_at'])
                : now(),
            'hotspot_session_id' => $payload['hotspot_session_id'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'mac_address' => $payload['mac_address'] ?? null,
            'source' => $payload['source'] ?? DataUsage::SOURCE_RADIUS,
            'started_at' => isset($payload['started_at']) ? Carbon::parse($payload['started_at']) : null,
            'ended_at' => isset($payload['ended_at']) ? Carbon::parse($payload['ended_at']) : null,
            'external_session_id' => $payload['external_session_id'] ?? null,
        ]);
    }

    /**
     * Finalize all sessions marked ended on a router during sync.
     */
    public function finalizeEndedHotspotSessions(Router $router): int
    {
        $sessions = HotspotSession::query()
            ->where('router_id', $router->id)
            ->where('status', 'ended')
            ->get();

        $recorded = 0;
        foreach ($sessions as $session) {
            $before = DataUsage::query()->where('hotspot_session_id', $session->id)->exists();
            $this->recordHotspotSessionEnd($session);
            if (! $before) {
                $recorded++;
            }
        }

        return $recorded;
    }

    private function subscriptionForSession(HotspotSession $session): ?\App\Models\Subscription
    {
        if (! $session->customer_id) {
            return null;
        }

        $at = $session->started_at ?? now();
        return \App\Models\Subscription::query()->withoutIspTenant()
            ->where('customer_id', $session->customer_id)
            ->where('start_date', '<=', $at->toDateString())
            ->where(function ($query) use ($at) {
                $query->where('ends_at', '>=', $at)
                    ->orWhere(function ($legacy) use ($at) {
                        $legacy->whereNull('ends_at')->where('end_date', '>=', $at->toDateString());
                    });
            })
            ->orderByDesc('start_date')
            ->first();
    }

    protected function parseMikrotikUptimeStart(?string $uptime, Carbon $now): ?Carbon
    {
        if ($uptime === null || $uptime === '') {
            return null;
        }

        $seconds = 0;
        if (preg_match('/(\d+)w/', $uptime, $m)) {
            $seconds += (int) $m[1] * 604800;
        }
        if (preg_match('/(\d+)d/', $uptime, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $uptime, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)s/', $uptime, $m)) {
            $seconds += (int) $m[1];
        }

        return $seconds > 0 ? $now->copy()->subSeconds($seconds) : null;
    }
}
