<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DataUsage;
use App\Models\HotspotSession;
use App\Models\Plan;
use App\Models\Router;
use App\Support\PlanPackageTypes;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataUsageMonitoringService
{
    /**
     * Customer-level usage summary for the monitoring index.
     */
    public function customerUsageQuery(int $ispId, Request $request): Builder
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subDays(30)->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $query = DataUsage::query()
            ->select([
                'data_usages.customer_id',
                DB::raw('SUM(data_usages.upload_bytes) as upload_bytes'),
                DB::raw('SUM(data_usages.download_bytes) as download_bytes'),
                DB::raw('SUM(data_usages.total_bytes) as total_bytes'),
                DB::raw('MAX(data_usages.recorded_at) as last_active_at'),
            ])
            ->where('data_usages.isp_id', $ispId)
            ->whereBetween('data_usages.recorded_at', [$dateFrom, $dateTo])
            ->groupBy('data_usages.customer_id');

        if ($request->filled('customer_id')) {
            $query->where('data_usages.customer_id', (int) $request->customer_id);
        }

        if ($request->filled('router_id')) {
            $query->where('data_usages.router_id', (int) $request->router_id);
        }

        if ($request->filled('package_type')) {
            $accessType = $request->package_type;
            $query->whereHas('plan', fn ($q) => $q->where('access_type', $accessType));
        }

        if ($request->filled('search')) {
            $term = SearchHelper::likePattern($request->search);
            if ($term !== null) {
                $query->where(function ($inner) use ($term) {
                    $inner->whereHas('customer', fn ($q) => $q->where('full_name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('phone_number', 'like', $term))
                        ->orWhere('data_usages.ip_address', 'like', $term)
                        ->orWhere('data_usages.mac_address', 'like', $term);
                });
            }
        }

        $sort = $request->input('sort', 'usage_desc');
        if ($sort === 'usage_asc') {
            $query->orderBy('total_bytes');
        } elseif ($sort === 'last_active') {
            $query->orderByDesc('last_active_at');
        } else {
            $query->orderByDesc('total_bytes');
        }

        return $query;
    }

    /**
     * @return array{
     *     daily: Collection,
     *     monthly: Collection,
     *     totals: array{upload: int, download: int, total: int, last_active: ?Carbon},
     *     current_session: ?HotspotSession
     * }
     */
    public function customerBreakdown(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now()->endOfDay();

        $base = DataUsage::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('recorded_at', [$from, $to]);

        $daily = (clone $base)
            ->select([
                DB::raw('DATE(recorded_at) as usage_date'),
                DB::raw('SUM(upload_bytes) as upload_bytes'),
                DB::raw('SUM(download_bytes) as download_bytes'),
                DB::raw('SUM(total_bytes) as total_bytes'),
            ])
            ->groupBy(DB::raw('DATE(recorded_at)'))
            ->orderBy('usage_date')
            ->get();

        $monthly = (clone $base)
            ->select([
                DB::raw("DATE_FORMAT(recorded_at, '%Y-%m') as usage_month"),
                DB::raw('SUM(upload_bytes) as upload_bytes'),
                DB::raw('SUM(download_bytes) as download_bytes'),
                DB::raw('SUM(total_bytes) as total_bytes'),
            ])
            ->groupBy(DB::raw("DATE_FORMAT(recorded_at, '%Y-%m')"))
            ->orderBy('usage_month')
            ->get();

        $totalsRow = (clone $base)
            ->selectRaw('COALESCE(SUM(upload_bytes), 0) as upload_bytes')
            ->selectRaw('COALESCE(SUM(download_bytes), 0) as download_bytes')
            ->selectRaw('COALESCE(SUM(total_bytes), 0) as total_bytes')
            ->selectRaw('MAX(recorded_at) as last_active_at')
            ->first();

        $currentSession = HotspotSession::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        return [
            'daily' => $daily,
            'monthly' => $monthly,
            'totals' => [
                'upload' => (int) ($totalsRow->upload_bytes ?? 0),
                'download' => (int) ($totalsRow->download_bytes ?? 0),
                'total' => (int) ($totalsRow->total_bytes ?? 0),
                'last_active' => $totalsRow?->last_active_at
                    ? Carbon::parse($totalsRow->last_active_at)
                    : null,
            ],
            'current_session' => $currentSession,
        ];
    }

    /**
     * Router-level analytics for capacity planning.
     *
     * @return Collection<int, object>
     */
    public function routerSummaries(int $ispId, Request $request): Collection
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subDays(30)->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $usageByRouter = DataUsage::query()
            ->select([
                'router_id',
                DB::raw('SUM(total_bytes) as total_bytes'),
                DB::raw('SUM(upload_bytes) as upload_bytes'),
                DB::raw('SUM(download_bytes) as download_bytes'),
                DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
            ])
            ->where('isp_id', $ispId)
            ->whereNotNull('router_id')
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->groupBy('router_id')
            ->get()
            ->keyBy('router_id');

        $activeUsers = HotspotSession::query()
            ->select('router_id', DB::raw('COUNT(*) as active_count'))
            ->where('isp_id', $ispId)
            ->where('status', 'active')
            ->groupBy('router_id')
            ->pluck('active_count', 'router_id');

        $peakHours = DataUsage::query()
            ->select([
                'router_id',
                DB::raw('HOUR(recorded_at) as peak_hour'),
                DB::raw('SUM(total_bytes) as hour_bytes'),
            ])
            ->where('isp_id', $ispId)
            ->whereNotNull('router_id')
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->groupBy('router_id', DB::raw('HOUR(recorded_at)'))
            ->orderByDesc('hour_bytes')
            ->get()
            ->groupBy('router_id')
            ->map(fn ($rows) => $rows->first());

        $routers = Router::forIsp($ispId)->orderBy('name')->get(['id', 'name', 'ip_address']);

        return $routers->map(function (Router $router) use ($usageByRouter, $activeUsers, $peakHours) {
            $usage = $usageByRouter->get($router->id);
            $peak = $peakHours->get($router->id);

            return (object) [
                'router' => $router,
                'total_bytes' => (int) ($usage->total_bytes ?? 0),
                'upload_bytes' => (int) ($usage->upload_bytes ?? 0),
                'download_bytes' => (int) ($usage->download_bytes ?? 0),
                'unique_customers' => (int) ($usage->unique_customers ?? 0),
                'active_users' => (int) ($activeUsers[$router->id] ?? 0),
                'peak_hour' => $peak?->peak_hour,
                'peak_hour_label' => $peak !== null
                    ? sprintf('%02d:00 – %02d:59', $peak->peak_hour, $peak->peak_hour)
                    : null,
            ];
        })->sortByDesc('total_bytes')->values();
    }

    /**
     * Workspace-level KPI stats for the index header.
     *
     * @return array<string, int|float>
     */
    public function workspaceStats(int $ispId, Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subDays(30)->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $totals = DataUsage::query()
            ->where('isp_id', $ispId)
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(SUM(total_bytes), 0) as total_bytes')
            ->selectRaw('COALESCE(SUM(upload_bytes), 0) as upload_bytes')
            ->selectRaw('COALESCE(SUM(download_bytes), 0) as download_bytes')
            ->selectRaw('COUNT(DISTINCT customer_id) as customers')
            ->first();

        return [
            'total_bytes' => (int) ($totals->total_bytes ?? 0),
            'upload_bytes' => (int) ($totals->upload_bytes ?? 0),
            'download_bytes' => (int) ($totals->download_bytes ?? 0),
            'customers' => (int) ($totals->customers ?? 0),
            'active_sessions' => HotspotSession::where('isp_id', $ispId)->where('status', 'active')->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function packageTypeFilterOptions(): array
    {
        return [
            PlanPackageTypes::HOTSPOT => 'Hotspot',
            PlanPackageTypes::PPPOE => 'PPPoE',
        ];
    }

    /**
     * @return Collection<int, Plan>
     */
    public function planOptions(int $ispId): Collection
    {
        return Plan::forIsp($ispId)
            ->whereIn('access_type', [PlanPackageTypes::HOTSPOT, PlanPackageTypes::PPPOE])
            ->orderBy('name')
            ->get(['id', 'name', 'access_type']);
    }
}
