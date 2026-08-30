<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $reportType = $request->get('type', 'revenue');
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $ispId = $request->get('isp_id');

        // Convert empty string to null
        if ($ispId === '') {
            $ispId = null;
        }

        try {
            $data = $this->getReportData($reportType, $startDate, $endDate, $ispId);
        } catch (\Exception $e) {
            \Log::error('Report generation error: ' . $e->getMessage());
            $data = [];
        }

        $isps = ISP::all();

        return view('admin.reports.index', compact('data', 'reportType', 'startDate', 'endDate', 'ispId', 'isps'));
    }

    protected function getReportData($type, $startDate, $endDate, $ispId = null)
    {
        $query = null;

        switch ($type) {
            case 'revenue':
                $baseQuery = Payment::where('payments.status', 'completed')
                    ->whereDate('payments.created_at', '>=', $startDate)
                    ->whereDate('payments.created_at', '<=', $endDate);
                
                if ($ispId) {
                    $baseQuery->where('payments.isp_id', $ispId);
                }

                return [
                    'total_revenue' => (clone $baseQuery)->sum('payments.amount'),
                    'payment_count' => (clone $baseQuery)->count(),
                    'by_method' => (clone $baseQuery)
                        ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
                        ->groupBy('payments.payment_method')
                        ->get(),
                    'by_isp' => (clone $baseQuery)
                        ->join('i_s_p_s', 'payments.isp_id', '=', 'i_s_p_s.id')
                        ->select('i_s_p_s.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
                        ->groupBy('i_s_p_s.id', 'i_s_p_s.name')
                        ->get(),
                    'daily' => (clone $baseQuery)
                        ->select(DB::raw('DATE(payments.created_at) as date'), DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get(),
                ];

            case 'customers':
                $baseQuery = Customer::whereDate('customers.created_at', '>=', $startDate)
                    ->whereDate('customers.created_at', '<=', $endDate);
                
                if ($ispId) {
                    $baseQuery->where('customers.isp_id', $ispId);
                }

                return [
                    'total' => (clone $baseQuery)->count(),
                    'by_status' => (clone $baseQuery)
                        ->select('customers.status', DB::raw('COUNT(*) as count'))
                        ->groupBy('customers.status')
                        ->get(),
                    'by_isp' => (clone $baseQuery)
                        ->join('i_s_p_s', 'customers.isp_id', '=', 'i_s_p_s.id')
                        ->select('i_s_p_s.name', DB::raw('COUNT(*) as count'))
                        ->groupBy('i_s_p_s.id', 'i_s_p_s.name')
                        ->get(),
                    'by_connection_type' => (clone $baseQuery)
                        ->select('customers.connection_type', DB::raw('COUNT(*) as count'))
                        ->groupBy('customers.connection_type')
                        ->get(),
                ];

            case 'invoices':
                $baseQuery = Invoice::whereDate('invoices.created_at', '>=', $startDate)
                    ->whereDate('invoices.created_at', '<=', $endDate);
                
                if ($ispId) {
                    $baseQuery->where('invoices.isp_id', $ispId);
                }

                return [
                    'total' => (clone $baseQuery)->count(),
                    'total_amount' => (clone $baseQuery)->sum('invoices.total_amount'),
                    'by_status' => (clone $baseQuery)
                        ->select('invoices.status', DB::raw('COUNT(*) as count'), DB::raw('SUM(invoices.total_amount) as total'))
                        ->groupBy('invoices.status')
                        ->get(),
                    'paid' => (clone $baseQuery)->where('invoices.status', 'paid')->sum('invoices.total_amount'),
                    'pending' => (clone $baseQuery)->where('invoices.status', 'pending')->sum('invoices.total_amount'),
                    'overdue' => (clone $baseQuery)->where('invoices.status', 'overdue')->sum('invoices.total_amount'),
                ];

            case 'plans':
                $query = Plan::query();
                
                if ($ispId) {
                    $query->where('isp_id', $ispId);
                }

                return [
                    'total' => $query->count(),
                    'by_access_type' => (clone $query)->select('access_type', DB::raw('COUNT(*) as count'))
                        ->groupBy('access_type')
                        ->get(),
                    'by_package_type' => (clone $query)->select('package_type', DB::raw('COUNT(*) as count'))
                        ->groupBy('package_type')
                        ->get(),
                    'popular' => DB::table('subscriptions')
                        ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                        ->select('plans.name', DB::raw('COUNT(*) as subscription_count'))
                        ->groupBy('plans.id', 'plans.name')
                        ->orderBy('subscription_count', 'desc')
                        ->limit(10)
                        ->get(),
                ];

            default:
                return [];
        }
    }
}
