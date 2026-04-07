<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the customer dashboard.
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        
        return view('customer.dashboard', compact('customer'));
    }
}
