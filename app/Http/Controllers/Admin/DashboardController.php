<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Check if user is super admin
        if (!auth()->user()->isSuperAdmin()) {
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

        $recentIsps = ISP::withCount(['customers', 'routers', 'plans'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentIsps'));
    }

    public function stats()
    {
        if (!auth()->user()->isSuperAdmin()) {
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
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isps = ISP::withCount(['customers', 'routers', 'plans'])->latest()->get();
        return response()->json($isps);
    }
}
