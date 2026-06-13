<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerUsageStat;
use App\Models\HotspotSession;
use App\Models\ISP;
use App\Models\Router;
use App\Support\InvoiceTax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    /**
     * Show customer login page
     */
    public function login(Request $request): View
    {
        return view('customer.login', [
            'landingIsp' => $request->attributes->get('landing_isp'),
        ]);
    }

    /**
     * Self-registration (hotspot customer) — only on tenant subdomain.
     */
    public function register(Request $request): View
    {
        return view('customer.register', [
            'landingIsp' => $request->attributes->get('landing_isp'),
        ]);
    }

    public function registerStore(Request $request): RedirectResponse
    {
        /** @var ISP $isp */
        $isp = $request->attributes->get('landing_isp');
        if (! $isp instanceof ISP) {
            abort(404);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:customers,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'location' => ['nullable', 'string', 'max:500'],
        ]);

        $routerId = Router::query()
            ->where('isp_id', $isp->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        $customer = Customer::create([
            'isp_id' => $isp->id,
            'router_id' => $routerId,
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'location' => $validated['location'] ?? '—',
            'connection_type' => 'hotspot',
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'status' => 'inactive',
            'installation_date' => now()->toDateString(),
        ]);

        session(['customer_id' => $customer->id]);

        return redirect()
            ->route('customer.checkout', ['customer' => $customer->id])
            ->with('success', 'Account created. Choose a plan to get connected.');
    }

    /**
     * Authenticate customer
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $landingIsp = $request->attributes->get('landing_isp');

        $query = Customer::query()
            ->where('username', $request->username)
            ->where('connection_type', 'hotspot');

        if ($landingIsp instanceof ISP) {
            $query->where('isp_id', $landingIsp->id);
        }

        $customer = $query->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return back()->withErrors(['error' => 'Invalid credentials.']);
        }

        session(['customer_id' => $customer->id]);

        return redirect()->route('customer.dashboard');
    }

    /**
     * Customer dashboard
     */
    public function dashboard()
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return redirect()->route('customer.login');
        }

        $customer = Customer::with(['activeSubscription.plan', 'router', 'isp'])
            ->findOrFail($customerId);

        // Get active session
        $activeSession = HotspotSession::where('customer_id', $customerId)
            ->where('status', 'active')
            ->latest()
            ->first();

        // Get usage stats (last 30 days)
        $usageStats = CustomerUsageStat::where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        // Get recent sessions
        $recentSessions = HotspotSession::where('customer_id', $customerId)
            ->orderBy('started_at', 'desc')
            ->take(10)
            ->get();

        // Get invoices
        $invoices = $customer->invoices()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Calculate totals
        $totalDataUsed = CustomerUsageStat::where('customer_id', $customerId)
            ->sum('total_bytes');

        return view('customer.dashboard', compact(
            'customer',
            'activeSession',
            'usageStats',
            'recentSessions',
            'invoices',
            'totalDataUsed'
        ));
    }

    /**
     * View usage statistics
     */
    public function usage()
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return redirect()->route('customer.login');
        }

        $customer = Customer::findOrFail($customerId);

        $usageStats = CustomerUsageStat::where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->paginate(30);

        return view('customer.usage', compact('customer', 'usageStats'));
    }

    /**
     * View invoices
     */
    public function invoices()
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return redirect()->route('customer.login');
        }

        $customer = Customer::findOrFail($customerId);

        $invoices = $customer->invoices()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('customer.invoices', compact('customer', 'invoices'));
    }

    /**
     * View session history
     */
    public function sessions()
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return redirect()->route('customer.login');
        }

        $customer = Customer::findOrFail($customerId);

        $sessions = HotspotSession::where('customer_id', $customerId)
            ->orderBy('started_at', 'desc')
            ->paginate(20);

        return view('customer.sessions', compact('customer', 'sessions'));
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return redirect()->route('customer.login');
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $customer = Customer::findOrFail($customerId);

        if (! Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $customer->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Update password on MikroTik if connected
        if ($customer->router && $customer->mikrotik_user_id) {
            try {
                $mikrotik = new \App\Services\MikroTikService($customer->router);
                if ($mikrotik->connect()) {
                    $connection = $mikrotik->getConnection();
                    $path = $customer->connection_type === 'hotspot'
                        ? '/ip/hotspot/user/set'
                        : '/ppp/secret/set';
                    $connection->comm($path, [
                        '.id' => $customer->mikrotik_user_id,
                        'password' => $request->new_password,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail
                \Log::error('Failed to update password on router: '.$e->getMessage());
            }
        }

        return back()->with('success', 'Password changed successfully.');
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget('customer_id');

        return redirect()->route('customer.login')->with('success', 'Logged out successfully.');
    }

    /**
     * Customer checkout page (for expired subscriptions)
     */
    public function checkout(Request $request)
    {
        $customerId = $request->input('customer') ?? session('customer_id');
        $paymentId = $request->input('payment');

        if (! $customerId) {
            return redirect()->route('hotspot.login')->with('error', 'Customer not found.');
        }

        $customer = Customer::with(['isp', 'router', 'invoices' => function ($query) {
            $query->where('status', 'pending')->latest();
        }])->findOrFail($customerId);

        // Ensure customer is hotspot user
        if ($customer->connection_type !== 'hotspot') {
            abort(403, 'This checkout is only for hotspot users.');
        }

        // Get pending invoices
        $pendingInvoices = $customer->invoices()
            ->where('status', 'pending')
            ->with('subscription.plan')
            ->latest()
            ->get();

        // Get specific payment if provided
        $specificPayment = null;
        if ($paymentId) {
            $specificPayment = \App\Models\Payment::where('customer_id', $customer->id)
                ->where('id', $paymentId)
                ->first();
        }

        // Get available plans for this ISP
        $availablePlans = \App\Models\Plan::where('isp_id', $customer->isp_id)
            ->where('access_type', 'hotspot')
            ->where('is_active', true)
            ->get();

        $paymentExperience = \App\Support\IspPaymentExperience::forIsp($customer->isp);

        return view('customer.checkout', compact(
            'customer',
            'pendingInvoices',
            'availablePlans',
            'specificPayment',
            'paymentExperience'
        ));
    }

    /**
     * Process plan selection and create invoice
     */
    public function selectPlan(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $plan = \App\Models\Plan::findOrFail($request->plan_id);

        // Verify plan belongs to customer's ISP
        if ($plan->isp_id !== $customer->isp_id) {
            return back()->withErrors(['error' => 'Invalid plan selected.']);
        }

        // Verify plan is for hotspot
        if ($plan->access_type !== 'hotspot') {
            return back()->withErrors(['error' => 'This plan is not for hotspot users.']);
        }

        // Create or update subscription
        // Use 'suspended' status until payment is completed, then it will be activated
        $subscription = \App\Models\Subscription::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
            ],
            [
                'start_date' => now(),
                'end_date' => now()->addDays($plan->duration_days),
                'renewal_date' => now()->addDays($plan->duration_days),
                'status' => 'suspended', // Will be 'active' after payment
                'auto_renew' => false,
            ]
        );

        // Check if invoice already exists for this subscription
        $invoice = \App\Models\Invoice::where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->first();

        if (! $invoice) {
            // Generate invoice number
            $ispPrefix = strtoupper(substr($customer->isp->name ?? 'ISP', 0, 3));
            $invoiceCount = \App\Models\Invoice::where('isp_id', $customer->isp_id)->count() + 1;
            $invoiceNumber = $ispPrefix.'-'.now()->format('Y').'-'.str_pad($invoiceCount, 6, '0', STR_PAD_LEFT);

            $amount = (float) $plan->price;
            $lines = InvoiceTax::computeForAmount($amount, $customer->isp);

            // Create invoice
            $invoice = \App\Models\Invoice::create([
                'isp_id' => $customer->isp_id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $lines['amount'],
                'tax' => $lines['tax'],
                'total_amount' => $lines['total_amount'],
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'status' => 'pending',
            ]);
        }

        // Redirect to checkout with invoice
        return redirect()->route('customer.checkout', [
            'customer' => $customer->id,
            'invoice' => $invoice->id,
        ])->with('success', 'Plan selected. Please complete payment to activate your subscription.');
    }
}
