<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\HotspotSession;
use App\Models\Invoice;
use App\Models\ISP;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class IspAdminOverviewService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ISP $isp): array
    {
        $ispId = $isp->id;

        $isp->load([
            'saasPlan',
            'paymentMethods' => fn ($q) => $q->orderBy('provider'),
            'platformSaasInvoices' => fn ($q) => $q->latest()->limit(5),
        ]);

        $isp->loadCount([
            'customers',
            'routers',
            'plans',
            'invoices',
            'payments',
            'users',
            'vouchers',
        ]);

        $activeCustomers = Customer::query()->where('isp_id', $ispId)->where('status', 'active')->count();
        $suspendedCustomers = Customer::query()->where('isp_id', $ispId)->where('status', 'suspended')->count();

        $activeRouters = Router::query()->where('isp_id', $ispId)->where('is_active', true)->count();
        $reachableRouters = Router::query()
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->where('reachability_status', 'online')
            ->count();

        $activeSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->whereHas('customer', fn ($q) => $q->where('isp_id', $ispId))
            ->count();

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->whereHas('customer', fn ($q) => $q->where('isp_id', $ispId))
            ->count();

        $activeSessions = HotspotSession::query()->where('isp_id', $ispId)->where('status', 'active')->count();

        $pendingInvoices = Invoice::query()->where('isp_id', $ispId)->where('status', 'pending')->count();
        $overdueInvoices = Invoice::query()->where('isp_id', $ispId)->where('status', 'overdue')->count();

        $totalRevenue = (float) Payment::query()->where('isp_id', $ispId)->where('status', 'completed')->sum('amount');
        $platformCommission = (float) Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->sum('platform_fee_amount');

        $currentMonthRevenue = (float) Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereRaw('COALESCE(processed_at, created_at) >= ?', [now()->startOfMonth()])
            ->sum('amount');

        $lastMonthRevenue = (float) Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereRaw('COALESCE(processed_at, created_at) >= ? AND COALESCE(processed_at, created_at) < ?', [
                now()->subMonth()->startOfMonth(),
                now()->startOfMonth(),
            ])
            ->sum('amount');

        $revenueChange = $lastMonthRevenue > 0
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : ($currentMonthRevenue > 0 ? 100 : 0);

        $monthlyRevenue = Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereRaw('COALESCE(processed_at, created_at) >= ?', [now()->subMonths(5)->startOfMonth()])
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(processed_at, created_at), '%Y-%m') as ym"),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $openTickets = Ticket::query()
            ->where('isp_id', $ispId)
            ->where('ticket_kind', Ticket::KIND_PLATFORM)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->count();

        $stats = [
            'active_customers' => $activeCustomers,
            'suspended_customers' => $suspendedCustomers,
            'active_routers' => $activeRouters,
            'reachable_routers' => $reachableRouters,
            'active_subscriptions' => $activeSubscriptions,
            'expiring_soon' => $expiringSoon,
            'active_sessions' => $activeSessions,
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdueInvoices,
            'total_revenue' => $totalRevenue,
            'platform_commission' => $platformCommission,
            'current_month_revenue' => $currentMonthRevenue,
            'revenue_change' => $revenueChange,
            'open_tickets' => $openTickets,
        ];

        $alerts = $this->buildAlerts($isp, $stats);

        return [
            'stats' => $stats,
            'alerts' => $alerts,
            'monthlyRevenue' => $monthlyRevenue,
            'recentPayments' => Payment::query()
                ->where('isp_id', $ispId)
                ->with('customer:id,full_name,username')
                ->latest(DB::raw('COALESCE(processed_at, created_at)'))
                ->limit(8)
                ->get(),
            'recentCustomers' => Customer::query()
                ->where('isp_id', $ispId)
                ->latest()
                ->limit(6)
                ->get(['id', 'full_name', 'username', 'status', 'connection_type', 'created_at']),
            'routers' => Router::query()
                ->where('isp_id', $ispId)
                ->orderBy('name')
                ->get(['id', 'name', 'ip_address', 'location', 'is_active', 'reachability_status', 'last_seen_at', 'last_health_pct', 'last_active_hotspot_users', 'last_active_pppoe_users']),
            'teamUsers' => $isp->users()->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']),
            'recentTickets' => Ticket::query()
                ->where('isp_id', $ispId)
                ->where('ticket_kind', Ticket::KIND_PLATFORM)
                ->latest()
                ->limit(5)
                ->get(['id', 'subject', 'status', 'priority', 'created_at']),
            'recentActivity' => ActivityLog::query()
                ->where('isp_id', $ispId)
                ->with('user:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'user_id', 'action', 'description', 'created_at']),
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return list<array{type: string, message: string}>
     */
    private function buildAlerts(ISP $isp, array $stats): array
    {
        $alerts = [];

        if ($isp->needsSaasOnboarding()) {
            $alerts[] = ['type' => 'warning', 'message' => 'SaaS onboarding is incomplete — operators may have limited access.'];
        }

        if (in_array($isp->saas_subscription_status, ['past_due', 'suspended'], true)) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Platform subscription is '.$isp->saas_subscription_status.' — review billing and access lock status.',
            ];
        }

        if ($isp->platform_access_locked) {
            $alerts[] = ['type' => 'warning', 'message' => 'ISP workspace is locked by platform policy.'];
        }

        if (! $isp->is_active) {
            $alerts[] = ['type' => 'danger', 'message' => 'ISP account is deactivated — customer network may be suspended.'];
        }

        if ($stats['overdue_invoices'] > 0) {
            $alerts[] = ['type' => 'warning', 'message' => $stats['overdue_invoices'].' overdue subscriber invoice(s).'];
        }

        if ($stats['suspended_customers'] > 0) {
            $alerts[] = ['type' => 'info', 'message' => $stats['suspended_customers'].' suspended customer(s).'];
        }

        if ($stats['active_routers'] > 0 && $stats['reachable_routers'] < $stats['active_routers']) {
            $offline = $stats['active_routers'] - $stats['reachable_routers'];
            $alerts[] = ['type' => 'danger', 'message' => $offline.' active router(s) appear offline or unreachable.'];
        }

        if ($stats['expiring_soon'] > 0) {
            $alerts[] = ['type' => 'info', 'message' => $stats['expiring_soon'].' subscription(s) expiring within 7 days.'];
        }

        if ($stats['open_tickets'] > 0) {
            $alerts[] = ['type' => 'info', 'message' => $stats['open_tickets'].' open platform helpdesk ticket(s).'];
        }

        return $alerts;
    }
}
