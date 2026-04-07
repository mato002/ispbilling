<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = Customer::with(['isp', 'router', 'subscriptions.plan']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('isp_id')) {
            $query->where('isp_id', $request->isp_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);
        $isps = \App\Models\ISP::all();

        return view('admin.customers.index', compact('customers', 'isps'));
    }
}
