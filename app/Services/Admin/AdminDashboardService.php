<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\ISP;
use App\Models\Invoice;
use App\Models\PlatformSaasInvoice;
use App\Models\Router;
use App\Support\ActivityLogAudit;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    /**
     * @return array{
     *     fleet_totals: array{online: int, offline: int, degraded: int, unconfigured: int, total: int},
     *     fleet_tenants: Collection<int, array<string, mixed>>,
     *     activity_feed: Collection<int, array<string, mixed>>,
     *     critical_alerts: list<array{severity: string, message: string, href: string|null}>
     * }
     */
    public function telemetry(): array
    {
        return [
            'fleet_totals' => $this->fleetTotals(),
            'fleet_tenants' => $this->fleetByTenant(),
            'activity_feed' => $this->activityFeed(),
            'critical_alerts' => $this->criticalAlerts(),
        ];
    }

    /**
     * @return array{online: int, offline: int, degraded: int, unconfigured: int, total: int}
     */
    public function fleetTotals(): array
    {
        $row = Router::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND reachability_status = ? THEN 1 ELSE 0 END) as online', ['online'])
            ->selectRaw('SUM(CASE WHEN is_active = 0 OR reachability_status = ? THEN 1 ELSE 0 END) as offline', ['offline'])
            ->selectRaw('SUM(CASE WHEN reachability_status IN (?, ?) THEN 1 ELSE 0 END) as degraded', ['degraded', 'rebooting'])
            ->selectRaw('SUM(CASE WHEN last_telemetry_at IS NULL OR reachability_status = ? OR reachability_status IS NULL THEN 1 ELSE 0 END) as unconfigured', ['unknown'])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'online' => (int) ($row->online ?? 0),
            'offline' => (int) ($row->offline ?? 0),
            'degraded' => (int) ($row->degraded ?? 0),
            'unconfigured' => (int) ($row->unconfigured ?? 0),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fleetByTenant(): Collection
    {
        $isps = ISP::query()
            ->where('is_active', true)
            ->withCount([
                'customers',
                'routers as routers_total',
                'routers as routers_online' => function ($q) {
                    $q->where('is_active', true)->where('reachability_status', 'online');
                },
                'routers as routers_offline' => function ($q) {
                    $q->where(function ($w) {
                        $w->where('is_active', false)->orWhere('reachability_status', 'offline');
                    });
                },
                'routers as routers_degraded' => function ($q) {
                    $q->whereIn('reachability_status', ['degraded', 'rebooting']);
                },
                'routers as routers_unconfigured' => function ($q) {
                    $q->where(function ($w) {
                        $w->whereNull('last_telemetry_at')
                            ->orWhere('reachability_status', 'unknown')
                            ->orWhereNull('reachability_status');
                    });
                },
            ])
            ->orderBy('name')
            ->get();

        return $isps->map(function (ISP $isp) {
            $counts = [
                'total' => (int) $isp->routers_total,
                'online' => (int) $isp->routers_online,
                'offline' => (int) $isp->routers_offline,
                'degraded' => (int) $isp->routers_degraded,
                'unconfigured' => (int) $isp->routers_unconfigured,
            ];

            return [
                'id' => $isp->id,
                'name' => $isp->name,
                'company_name' => $isp->company_name,
                'customers_count' => (int) $isp->customers_count,
                'saas_status' => $isp->saas_subscription_status,
                'platform_locked' => (bool) $isp->platform_access_locked,
                'counts' => $counts,
                'health' => $this->tenantFleetHealth($counts),
                'show_url' => route('admin.isps.show', $isp),
                'edit_url' => route('admin.isps.edit', $isp),
            ];
        })->sortByDesc(function (array $row) {
            return match ($row['health']) {
                'critical' => 4,
                'degraded' => 3,
                'unconfigured' => 2,
                'healthy' => 0,
                default => 1,
            };
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function activityFeed(int $limit = 28): Collection
    {
        return ActivityLog::query()
            ->with(['user:id,name', 'isp:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (ActivityLog $log) {
                $tenant = $log->isp?->name ?? 'Platform';
                $actor = $log->user?->name;
                $detail = $log->description ?: ActivityLogAudit::eventTitle($log);

                $line = $actor
                    ? "{$tenant} · {$actor} — {$detail}"
                    : "{$tenant} — {$detail}";

                return [
                    'id' => $log->id,
                    'time' => $log->created_at?->timezone(config('app.timezone'))->format('g:i A'),
                    'time_full' => $log->created_at?->timezone(config('app.timezone'))->format('M j, g:i A'),
                    'line' => $line,
                    'severity' => ActivityLogAudit::severity($log),
                    'module' => ActivityLogAudit::moduleKey($log),
                    'href' => route('admin.audit.activity.show', $log),
                ];
            });
    }

    /**
     * @return list<array{severity: string, message: string, href: string|null}>
     */
    public function criticalAlerts(): array
    {
        $alerts = [];

        $overdueSaas = PlatformSaasInvoice::query()
            ->with('isp:id,name')
            ->where('status', PlatformSaasInvoice::STATUS_PENDING)
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        foreach ($overdueSaas as $invoice) {
            $ispName = $invoice->isp?->name ?? 'Unknown ISP';
            $alerts[] = [
                'severity' => 'critical',
                'message' => "Platform subscription invoice overdue — {$ispName} ({$invoice->invoice_number})",
                'href' => $invoice->isp ? route('admin.isps.show', $invoice->isp_id) : route('admin.finance.index'),
            ];
        }

        $lockedTenants = ISP::query()
            ->where('platform_access_locked', true)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name']);

        foreach ($lockedTenants as $isp) {
            $alerts[] = [
                'severity' => 'critical',
                'message' => "Tenant console locked — {$isp->name} (platform billing)",
                'href' => route('admin.isps.show', $isp),
            ];
        }

        $pastDueTenants = ISP::query()
            ->where('saas_subscription_status', 'past_due')
            ->where('platform_access_locked', false)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name']);

        foreach ($pastDueTenants as $isp) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "SaaS subscription past due — {$isp->name}",
                'href' => route('admin.isps.show', $isp),
            ];
        }

        $offlineRouters = (int) Router::query()
            ->where(function ($q) {
                $q->where('is_active', false)->orWhere('reachability_status', 'offline');
            })
            ->count();

        if ($offlineRouters > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "{$offlineRouters} MikroTik router(s) offline or unreachable across the fleet",
                'href' => route('admin.network.index'),
            ];
        }

        $overdueCustomerInvoices = Invoice::query()
            ->with('isp:id,name')
            ->where('status', 'overdue')
            ->latest('due_date')
            ->limit(5)
            ->get();

        foreach ($overdueCustomerInvoices as $invoice) {
            $ispName = $invoice->isp?->name ?? 'ISP';
            $alerts[] = [
                'severity' => 'warning',
                'message' => "Customer invoice overdue — {$ispName}",
                'href' => route('admin.isps.show', $invoice->isp_id),
            ];
        }

        $failedLogins = ActivityLog::query()
            ->where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHours(6))
            ->count();

        if ($failedLogins >= 5) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "{$failedLogins} failed login attempts in the last 6 hours",
                'href' => route('admin.audit.security'),
            ];
        }

        return $alerts;
    }

    public function tenantFleetHealth(array $counts): string
    {
        if ($counts['total'] === 0) {
            return 'unconfigured';
        }

        if ($counts['offline'] > 0 && $counts['online'] === 0) {
            return 'critical';
        }

        if ($counts['offline'] > 0) {
            return 'critical';
        }

        if ($counts['degraded'] > 0) {
            return 'degraded';
        }

        if ($counts['unconfigured'] > 0 && $counts['online'] === 0) {
            return 'unconfigured';
        }

        if ($counts['unconfigured'] > 0) {
            return 'degraded';
        }

        return 'healthy';
    }
}
