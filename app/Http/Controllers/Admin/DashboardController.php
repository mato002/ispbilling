<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ISP;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(AdminDashboardService $dashboard)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $stats = [
            'total_isps' => ISP::count(),
            'active_isps' => ISP::where('is_active', true)->count(),
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'total_revenue' => (float) (Payment::where('status', 'completed')->sum('amount') ?? 0),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
        ];

        $telemetry = $dashboard->telemetry();

        $recentIsps = ISP::withCount(['customers', 'routers', 'plans'])
            ->latest()
            ->take(12)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentIsps' => $recentIsps,
            'fleetTotals' => $telemetry['fleet_totals'],
            'fleetTenants' => $telemetry['fleet_tenants'],
            'activityFeed' => $telemetry['activity_feed'],
            'criticalAlerts' => $telemetry['critical_alerts'],
        ]);
    }

    public function stats()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $stats = [
            'total_isps' => ISP::count(),
            'active_isps' => ISP::where('is_active', true)->count(),
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'total_revenue' => (float) (Payment::where('status', 'completed')->sum('amount') ?? 0),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
        ];

        return response()->json($stats);
    }

    public function isps()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isps = ISP::withCount(['customers', 'routers', 'plans'])->latest()->get();

        return response()->json($isps);
    }
}
