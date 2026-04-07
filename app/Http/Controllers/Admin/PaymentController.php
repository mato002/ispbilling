<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = Payment::with(['isp', 'customer', 'invoice']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('isp_id')) {
            $query->where('isp_id', $request->isp_id);
        }

        $payments = $query->latest()->paginate(20);
        $isps = \App\Models\ISP::all();

        return view('admin.payments.index', compact('payments', 'isps'));
    }
}
