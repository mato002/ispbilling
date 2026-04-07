<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\PaymentAuditLog;
use Illuminate\Http\Request;

class PaymentAuditLogController extends BaseController
{
    public function index(Request $request)
    {
        $ispId = $this->getIspId();
        
        $query = PaymentAuditLog::where('isp_id', $ispId)
            ->with(['payment', 'customer', 'invoice', 'user'])
            ->latest();

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by payment
        if ($request->has('payment_id') && $request->payment_id) {
            $query->where('payment_id', $request->payment_id);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        return view('isp.logs.payment-audit', compact('logs'));
    }

    public function show(PaymentAuditLog $paymentAuditLog)
    {
        $this->ensureIspAccess($paymentAuditLog);

        $paymentAuditLog->load(['payment', 'customer', 'invoice', 'user', 'isp']);

        return view('isp.logs.payment-audit-show', compact('paymentAuditLog'));
    }
}





