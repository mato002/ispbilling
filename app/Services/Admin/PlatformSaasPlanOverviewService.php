<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\ISP;
use App\Models\PlatformSaasInvoice;
use App\Models\PlatformSaasPlan;
use App\Models\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PlatformSaasPlanOverviewService
{
    /**
     * @return array{
     *     stats: array<string, mixed>,
     *     status_breakdown: Collection<string, int>,
     *     alerts: array<int, array{type: string, message: string}>,
     *     tenants: Collection<int, ISP>,
     *     recent_invoices: Collection<int, PlatformSaasInvoice>,
     *     monthly_signups: Collection<int, object>
     * }
     */
    public function build(PlatformSaasPlan $plan): array
    {
        $planId = $plan->id;
        $tenantIds = ISP::query()->where('platform_saas_plan_id', $planId)->select('id');

        $tenantCount = (clone $tenantIds)->count();
        $activeTenantCount = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->where('is_active', true)
            ->count();

        $statusBreakdown = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->select('saas_subscription_status', DB::raw('count(*) as aggregate'))
            ->groupBy('saas_subscription_status')
            ->pluck('aggregate', 'saas_subscription_status');

        $totalCustomers = Customer::query()->whereIn('isp_id', $tenantIds)->count();
        $activeCustomers = Customer::query()
            ->whereIn('isp_id', $tenantIds)
            ->where('status', 'active')
            ->count();

        $totalRouters = Router::query()->whereIn('isp_id', $tenantIds)->count();

        $paidRevenue = (float) PlatformSaasInvoice::query()
            ->where('platform_saas_plan_id', $planId)
            ->where('status', PlatformSaasInvoice::STATUS_PAID)
            ->selectRaw('SUM(subscription_amount + setup_fee_amount) as total')
            ->value('total');

        $pendingInvoiceCount = PlatformSaasInvoice::query()
            ->where('platform_saas_plan_id', $planId)
            ->where('status', PlatformSaasInvoice::STATUS_PENDING)
            ->count();

        $pendingInvoiceAmount = (float) PlatformSaasInvoice::query()
            ->where('platform_saas_plan_id', $planId)
            ->where('status', PlatformSaasInvoice::STATUS_PENDING)
            ->selectRaw('SUM(subscription_amount + setup_fee_amount) as total')
            ->value('total');

        $billingTenantCount = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->whereIn('saas_subscription_status', ['active', 'trialing', 'past_due'])
            ->count();

        $mrrEstimate = $billingTenantCount * (float) $plan->monthly_price;

        $tenantsAtCustomerLimit = 0;
        if ($plan->max_customers !== null) {
            $tenantsAtCustomerLimit = ISP::query()
                ->where('platform_saas_plan_id', $planId)
                ->withCount('customers')
                ->get()
                ->filter(fn (ISP $isp) => $isp->customers_count >= $plan->max_customers)
                ->count();
        }

        $onboardingPending = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->whereNull('saas_onboarding_completed_at')
            ->count();

        $stats = [
            'tenant_count' => $tenantCount,
            'active_tenant_count' => $activeTenantCount,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'total_routers' => $totalRouters,
            'paid_revenue' => $paidRevenue,
            'pending_invoice_count' => $pendingInvoiceCount,
            'pending_invoice_amount' => $pendingInvoiceAmount,
            'mrr_estimate' => $mrrEstimate,
            'billing_tenant_count' => $billingTenantCount,
            'tenants_at_customer_limit' => $tenantsAtCustomerLimit,
            'onboarding_pending' => $onboardingPending,
            'trialing_count' => (int) ($statusBreakdown['trialing'] ?? 0),
            'active_subscription_count' => (int) ($statusBreakdown['active'] ?? 0),
            'past_due_count' => (int) ($statusBreakdown['past_due'] ?? 0),
            'suspended_count' => (int) ($statusBreakdown['suspended'] ?? 0),
        ];

        $tenants = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->withCount(['customers', 'routers', 'users'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $recentInvoices = PlatformSaasInvoice::query()
            ->where('platform_saas_plan_id', $planId)
            ->with('isp:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $monthlySignups = ISP::query()
            ->where('platform_saas_plan_id', $planId)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return [
            'stats' => $stats,
            'status_breakdown' => $statusBreakdown,
            'alerts' => $this->buildAlerts($plan, $stats),
            'tenants' => $tenants,
            'recent_invoices' => $recentInvoices,
            'monthly_signups' => $monthlySignups,
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return array<int, array{type: string, message: string}>
     */
    private function buildAlerts(PlatformSaasPlan $plan, array $stats): array
    {
        $alerts = [];

        if (! $plan->is_active && $stats['tenant_count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'This plan is marked inactive but still has '.$stats['tenant_count'].' tenant(s) assigned.',
            ];
        }

        if ($stats['past_due_count'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => $stats['past_due_count'].' tenant(s) on this plan are past due on platform billing.',
            ];
        }

        if ($stats['pending_invoice_count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $stats['pending_invoice_count'].' pending platform invoice(s) totalling KES '.number_format($stats['pending_invoice_amount'], 2).'.',
            ];
        }

        if ($stats['tenants_at_customer_limit'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $stats['tenants_at_customer_limit'].' tenant(s) have reached the subscriber cap for this tier.',
            ];
        }

        if ($stats['onboarding_pending'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $stats['onboarding_pending'].' tenant(s) are still completing SaaS onboarding on this plan.',
            ];
        }

        return $alerts;
    }
}
