<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Jobs\GenerateInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function invoices(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Invoice::where('isp_id', $ispId)
            ->with(['customer', 'subscription.plan']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->latest()->paginate(20);

        return response()->json($invoices);
    }

    public function generateInvoices()
    {
        $ispId = auth()->user()->isp_id;
        $isp = \App\Models\ISP::findOrFail($ispId);

        GenerateInvoices::dispatch($isp);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generation job queued',
        ]);
    }

    public function payments(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Payment::where('isp_id', $ispId)
            ->with(['customer', 'invoice']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->latest()->paginate(20);

        return response()->json($payments);
    }

    public function stats()
    {
        $ispId = auth()->user()->isp_id;

        $stats = [
            'total_revenue' => Payment::where('isp_id', $ispId)
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_invoices' => Invoice::where('isp_id', $ispId)
                ->where('status', 'pending')
                ->count(),
            'overdue_invoices' => Invoice::where('isp_id', $ispId)
                ->where('status', 'overdue')
                ->count(),
            'total_customers' => \App\Models\Customer::where('isp_id', $ispId)->count(),
            'active_customers' => \App\Models\Customer::where('isp_id', $ispId)
                ->where('status', 'active')
                ->count(),
            'monthly_revenue' => Payment::where('isp_id', $ispId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        return response()->json($stats);
    }
}
