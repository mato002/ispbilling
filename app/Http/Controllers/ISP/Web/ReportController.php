<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $stats = [
            'total_customers' => Customer::where('isp_id', $ispId)->count(),
            'active_customers' => Customer::where('isp_id', $ispId)->where('status', 'active')->count(),
            'suspended_customers' => Customer::where('isp_id', $ispId)->where('status', 'suspended')->count(),
            'total_revenue' => Payment::where('isp_id', $ispId)->where('status', 'completed')->sum('amount'),
            'pending_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'overdue')->count(),
            'active_subscriptions' => Subscription::whereHas('customer', function ($q) use ($ispId) {
                $q->where('isp_id', $ispId);
            })->where('status', 'active')->count(),
        ];

        return view('isp.reports.index', compact('stats', 'startDate', 'endDate'));
    }

    /**
     * Get Active vs Suspended Customers Report (Chart-ready)
     */
    public function customerStatusReport(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $data = Customer::where('isp_id', $ispId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Format for chart (labels and data arrays)
        $labels = [];
        $values = [];
        $colors = [
            'active' => '#10b981',      // green
            'suspended' => '#ef4444',   // red
            'expired' => '#f59e0b',     // yellow
            'inactive' => '#6b7280',    // gray
        ];

        foreach ($data as $item) {
            $labels[] = ucfirst($item->status);
            $values[] = (int) $item->count;
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => $values,
                    'backgroundColor' => array_map(function($status) use ($colors) {
                        return $colors[strtolower($status)] ?? '#6b7280';
                    }, $labels),
                ]
            ],
            'summary' => [
                'total' => array_sum($values),
                'active' => $data->where('status', 'active')->first()->count ?? 0,
                'suspended' => $data->where('status', 'suspended')->first()->count ?? 0,
            ]
        ]);
    }

    /**
     * Get Revenue per Plan Report (Chart-ready)
     */
    public function revenuePerPlanReport(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $startDate = $request->get('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $data = Payment::where('payments.isp_id', $ispId)
            ->where('payments.status', 'completed')
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('subscriptions', 'invoices.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                'plans.id',
                'plans.name',
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT payments.id) as payment_count'),
                DB::raw('COUNT(DISTINCT subscriptions.customer_id) as customer_count')
            )
            ->groupBy('plans.id', 'plans.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $labels = [];
        $revenue = [];
        $paymentCounts = [];
        $customerCounts = [];

        foreach ($data as $item) {
            $labels[] = $item->name;
            $revenue[] = (float) $item->total_revenue;
            $paymentCounts[] = (int) $item->payment_count;
            $customerCounts[] = (int) $item->customer_count;
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenue,
                    'backgroundColor' => '#3b82f6', // blue
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ]
            ],
            'summary' => [
                'total_revenue' => array_sum($revenue),
                'total_payments' => array_sum($paymentCounts),
                'total_customers' => array_sum($customerCounts),
            ],
            'details' => $data->map(function($item) {
                return [
                    'plan_id' => $item->id,
                    'plan_name' => $item->name,
                    'revenue' => (float) $item->total_revenue,
                    'payment_count' => (int) $item->payment_count,
                    'customer_count' => (int) $item->customer_count,
                ];
            })
        ]);
    }

    /**
     * Get Monthly Collections Report (Chart-ready)
     */
    public function monthlyCollectionsReport(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $months = $request->get('months', 12); // Default to last 12 months
        $startDate = $request->get('start_date', now()->subMonths($months - 1)->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $data = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('COUNT(DISTINCT customer_id) as customer_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months with zero values
        $labels = [];
        $revenue = [];
        $paymentCounts = [];
        $customerCounts = [];

        $start = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();

        $monthMap = [];
        foreach ($data as $item) {
            $monthMap[$item->month] = $item;
        }

        $current = $start->copy();
        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $labels[] = $current->format('M Y');
            
            if (isset($monthMap[$monthKey])) {
                $item = $monthMap[$monthKey];
                $revenue[] = (float) $item->total_revenue;
                $paymentCounts[] = (int) $item->payment_count;
                $customerCounts[] = (int) $item->customer_count;
            } else {
                $revenue[] = 0;
                $paymentCounts[] = 0;
                $customerCounts[] = 0;
            }

            $current->addMonth();
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ]
            ],
            'summary' => [
                'total_revenue' => array_sum($revenue),
                'average_monthly' => count($revenue) > 0 ? array_sum($revenue) / count($revenue) : 0,
                'total_payments' => array_sum($paymentCounts),
                'total_customers' => array_sum($customerCounts),
                'peak_month' => $labels[array_search(max($revenue), $revenue)] ?? null,
                'peak_revenue' => max($revenue),
            ],
            'details' => array_map(function($label, $rev, $payments, $customers) {
                return [
                    'month' => $label,
                    'revenue' => $rev,
                    'payment_count' => $payments,
                    'customer_count' => $customers,
                ];
            }, $labels, $revenue, $paymentCounts, $customerCounts)
        ]);
    }
}

