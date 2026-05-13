<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Router;
use App\Models\HotspotSession;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $ispId = auth()->user()->isp_id;
        
        if (!$ispId) {
            return redirect()->route('dashboard')->with('error', 'No ISP associated with your account.');
        }

        // Basic Stats
        $stats = [
            'total_customers' => Customer::where('isp_id', $ispId)->count(),
            'active_customers' => Customer::where('isp_id', $ispId)->where('status', 'active')->count(),
            'suspended_customers' => Customer::where('isp_id', $ispId)->where('status', 'suspended')->count(),
            'total_revenue' => Payment::where('isp_id', $ispId)->where('status', 'completed')->sum('amount'),
            'monthly_revenue' => Payment::where('isp_id', $ispId)
                ->where('status', 'completed')
                ->whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->sum('amount'),
            'pending_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'overdue')->count(),
            'active_subscriptions' => Subscription::query()
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->count(),
            'expiring_soon' => Subscription::query()
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(7)])
                ->count(),
            'active_sessions' => HotspotSession::where('isp_id', $ispId)->where('status', 'active')->count(),
            'total_routers' => Router::where('isp_id', $ispId)->count(),
            'active_routers' => Router::where('isp_id', $ispId)->where('is_active', true)->count(),
            'total_vouchers' => Voucher::where('isp_id', $ispId)->count(),
            'active_vouchers' => Voucher::where('isp_id', $ispId)->where('status', 'active')->count(),
        ];

        // Revenue Trends (Last 30 days)
        $revenueTrends = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->where(function($query) {
                $query->where('processed_at', '>=', now()->subDays(30))
                      ->orWhere(function($q) {
                          // If processed_at is null, use created_at
                          $q->whereNull('processed_at')
                            ->where('created_at', '>=', now()->subDays(30));
                      });
            })
            ->selectRaw('DATE(COALESCE(processed_at, created_at)) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Customer Growth (Last 12 months)
        $customerGrowth = Customer::where('isp_id', $ispId)
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                    'count' => (int) $item->count,
                ];
            });

        // Connection Type Breakdown
        $connectionBreakdown = Customer::where('isp_id', $ispId)
            ->select('connection_type', DB::raw('COUNT(*) as count'))
            ->groupBy('connection_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->connection_type => $item->count];
            });

        // Payment Method Breakdown (Last 30 days)
        $paymentMethodBreakdown = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->where(function($query) {
                $query->where('processed_at', '>=', now()->subDays(30))
                      ->orWhere(function($q) {
                          $q->whereNull('processed_at')
                            ->where('created_at', '>=', now()->subDays(30));
                      });
            })
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Top Customers by Revenue (Last 30 days)
        $topCustomers = Customer::where('isp_id', $ispId)
            ->withSum(['payments' => function ($query) {
                $query->where('status', 'completed')
                    ->where('processed_at', '>=', now()->subDays(30));
            }], 'amount')
            ->orderBy('payments_sum_amount', 'desc')
            ->take(10)
            ->get();

        // Top Plans by Subscriptions
        $topPlans = Plan::where('isp_id', $ispId)
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('subscriptions_count', 'desc')
            ->take(10)
            ->get();

        // Recent Payments
        $recentPayments = Payment::where('isp_id', $ispId)
            ->with('customer')
            ->latest('processed_at')
            ->take(10)
            ->get();

        // Recent Customers
        $recentCustomers = Customer::where('isp_id', $ispId)
            ->with(['subscriptions' => function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->with('plan')
                    ->latest();
            }])
            ->latest()
            ->take(10)
            ->get();

        // Recent Invoices
        $recentInvoices = Invoice::where('isp_id', $ispId)
            ->with('customer', 'subscription.plan')
            ->latest()
            ->take(10)
            ->get();

        // Monthly Revenue Comparison
        $currentMonthRevenue = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->sum('amount');

        $lastMonthRevenue = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereMonth('processed_at', now()->subMonth()->month)
            ->whereYear('processed_at', now()->subMonth()->year)
            ->sum('amount');

        $revenueChange = $lastMonthRevenue > 0 
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;

        // Customer Growth Comparison
        $currentMonthCustomers = Customer::where('isp_id', $ispId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonthCustomers = Customer::where('isp_id', $ispId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $customerChange = $lastMonthCustomers > 0 
            ? (($currentMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100 
            : ($currentMonthCustomers > 0 ? 100 : 0);

        // Active Sessions by Router
        $sessionsByRouter = HotspotSession::where('isp_id', $ispId)
            ->where('status', 'active')
            ->select('router_id', DB::raw('COUNT(*) as count'))
            ->groupBy('router_id')
            ->get();
        
        // Load routers separately
        $routerIds = $sessionsByRouter->pluck('router_id')->filter()->unique();
        $routers = Router::whereIn('id', $routerIds)->get()->keyBy('id');
        $sessionsByRouter->each(function ($session) use ($routers) {
            $session->setRelation('router', $routers->get($session->router_id));
        });

        // Alerts/Notifications
        $alerts = [];
        
        if ($stats['overdue_invoices'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$stats['overdue_invoices']} overdue invoice(s) require attention",
                'link' => route('isp.invoices.index', ['status' => 'overdue']),
            ];
        }

        if ($stats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$stats['expiring_soon']} subscription(s) expiring in the next 7 days",
                'link' => route('isp.subscriptions.index'),
            ];
        }

        if ($stats['suspended_customers'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$stats['suspended_customers']} customer(s) are suspended",
                'link' => route('isp.customers.index', ['status' => 'suspended']),
            ];
        }

        $inactiveRouters = $stats['total_routers'] - $stats['active_routers'];
        if ($inactiveRouters > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$inactiveRouters} router(s) are inactive",
                'link' => route('isp.routers.index'),
            ];
        }

        return view('dashboard', compact(
            'stats',
            'revenueTrends',
            'customerGrowth',
            'connectionBreakdown',
            'paymentMethodBreakdown',
            'topCustomers',
            'topPlans',
            'recentPayments',
            'recentCustomers',
            'recentInvoices',
            'currentMonthRevenue',
            'lastMonthRevenue',
            'revenueChange',
            'currentMonthCustomers',
            'lastMonthCustomers',
            'customerChange',
            'sessionsByRouter',
            'alerts'
        ));
    }
}

