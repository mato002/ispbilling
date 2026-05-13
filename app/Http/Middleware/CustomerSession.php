<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('customer_id')) {
            return redirect()->route('customer.login');
        }

        $landingIsp = $request->attributes->get('landing_isp');
        if ($landingIsp) {
            $customer = Customer::query()->find(session('customer_id'));
            if ($customer && (int) $customer->isp_id !== (int) $landingIsp->id) {
                session()->forget('customer_id');

                return redirect()
                    ->route('customer.login')
                    ->withErrors(['error' => 'Please sign in again for this network.']);
            }
        }

        return $next($request);
    }
}





