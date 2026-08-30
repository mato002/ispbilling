<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\HotspotSession;
use App\Models\Payment;
use App\Models\Router;
use Illuminate\Support\Collection;

class AuthPanelStatsService
{
    /**
     * Live metrics for the auth panel marketing cards.
     *
     * @return array<string, mixed>
     */
    public function build(?int $ispId = null): array
    {
        $currency = $this->resolveCurrency($ispId);

        $activeSubscribers = $this->countActiveSubscribers($ispId);
        $growthPct = $this->subscriberGrowthPercent($ispId);
        $sparkline = $this->subscriberSparkline($ispId);

        $routerQuery = Router::query()->when($ispId, fn ($q) => $q->where('isp_id', $ispId));
        $activeRouterQuery = (clone $routerQuery)->where('is_active', true);

        $routersTotal = (clone $activeRouterQuery)->count();
        $routersHealthy = (clone $activeRouterQuery)->where('reachability_status', 'online')->count();
        $routersReporting = (clone $activeRouterQuery)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subMinutes(30))
            ->count();

        $routerSessionSum = (int) (clone $activeRouterQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(last_active_hotspot_users, 0) + COALESCE(last_active_pppoe_users, 0)), 0) as total')
            ->value('total');

        $hotspotSessions = HotspotSession::query()
            ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
            ->where('status', 'active')
            ->count();

        $onlineSessions = $routerSessionSum > 0 ? $routerSessionSum : $hotspotSessions;

        [$revenueToday, $transactionsToday] = $this->revenueToday($ispId);

        return [
            'scoped_to_isp' => $ispId !== null,
            'currency' => $currency,
            'active_subscribers' => $activeSubscribers,
            'subscriber_growth_pct' => $growthPct,
            'subscriber_sparkline' => $sparkline,
            'online_sessions' => $onlineSessions,
            'routers_reporting' => $routersReporting,
            'revenue_today' => $revenueToday,
            'revenue_today_formatted' => $this->formatMoney($revenueToday, $currency),
            'transactions_today' => $transactionsToday,
            'routers_healthy' => $routersHealthy,
            'routers_total' => $routersTotal,
            'router_health_label' => $this->routerHealthLabel($routersHealthy, $routersTotal),
            'router_health_tone' => $this->routerHealthTone($routersHealthy, $routersTotal),
        ];
    }

    protected function countActiveSubscribers(?int $ispId): int
    {
        return Customer::query()
            ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
            ->where('status', 'active')
            ->count();
    }

    protected function subscriberGrowthPercent(?int $ispId): ?float
    {
        $recent = Customer::query()
            ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $previous = Customer::query()
            ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        if ($previous === 0) {
            return $recent > 0 ? 100.0 : null;
        }

        return round((($recent - $previous) / $previous) * 100, 1);
    }

    /**
     * @return list<int>
     */
    protected function subscriberSparkline(?int $ispId): array
    {
        /** @var Collection<int, int> $counts */
        $counts = collect(range(11, 0))->map(function (int $daysAgo) use ($ispId): int {
            $day = now()->subDays($daysAgo)->toDateString();

            return Customer::query()
                ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
                ->whereDate('created_at', $day)
                ->count();
        });

        $max = max($counts->max() ?? 0, 1);

        return $counts
            ->map(fn (int $count): int => max(8, (int) round(($count / $max) * 26)))
            ->values()
            ->all();
    }

    /**
     * @return array{0: float, 1: int}
     */
    protected function revenueToday(?int $ispId): array
    {
        $query = Payment::query()
            ->when($ispId, fn ($q) => $q->where('isp_id', $ispId))
            ->where('status', 'completed')
            ->whereRaw('DATE(COALESCE(processed_at, created_at)) = ?', [now()->toDateString()]);

        return [
            (float) (clone $query)->sum('amount'),
            (clone $query)->count(),
        ];
    }

    protected function resolveCurrency(?int $ispId): string
    {
        if ($ispId) {
            $isp = \App\Models\ISP::query()->find($ispId);

            if ($isp?->currency) {
                return strtoupper((string) $isp->currency);
            }
        }

        if ($user = auth()->user()) {
            $isp = $user->isp;

            if ($isp?->currency) {
                return strtoupper((string) $isp->currency);
            }
        }

        return 'KES';
    }

    protected function formatMoney(float $amount, string $currency): string
    {
        $symbol = match (strtoupper($currency)) {
            'KES' => 'KSh',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency.' ',
        };

        return $symbol.' '.number_format($amount, 0);
    }

    protected function routerHealthLabel(int $healthy, int $total): string
    {
        if ($total === 0) {
            return 'No routers';
        }

        if ($healthy === $total) {
            return 'Healthy';
        }

        if ($healthy === 0) {
            return 'Offline';
        }

        return 'Degraded';
    }

    protected function routerHealthTone(int $healthy, int $total): string
    {
        if ($total === 0) {
            return 'muted';
        }

        if ($healthy === $total) {
            return 'ok';
        }

        if ($healthy === 0) {
            return 'bad';
        }

        return 'warn';
    }
}
