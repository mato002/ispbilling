<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoiceTax;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Invoice::where('isp_id', $ispId)
            ->with(['customer', 'subscription']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->latest()->paginate(20)->appends($request->query());

        return view('isp.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice, Request $request)
    {
        if ($invoice->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $invoice->load(['customer', 'subscription', 'payments', 'isp']);

        // Check if PDF is requested
        if ($request->has('pdf') && $request->pdf == '1') {
            return $this->generatePdf($invoice);
        }

        return view('isp.invoices.show', compact('invoice'));
    }

    protected function generatePdf(Invoice $invoice)
    {
        // Check if Dompdf is available
        if (! class_exists(Dompdf::class)) {
            Log::warning('Dompdf class not found. PDF generation unavailable.', [
                'invoice_id' => $invoice->id,
            ]);

            return redirect()->route('isp.invoices.show', $invoice)
                ->with('error', 'PDF generation is not available. Please run: composer require dompdf/dompdf');
        }

        try {
            $dompdf = new Dompdf;
            $html = view('isp.invoices.pdf', compact('invoice'))->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->stream('invoice-'.$invoice->invoice_number.'.pdf');
        } catch (\Exception $e) {
            Log::error('PDF generation failed: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('isp.invoices.show', $invoice)
                ->with('error', 'Failed to generate PDF. Please contact support if this issue persists.');
        }
    }

    /**
     * Create invoice for a customer
     */
    public function createForCustomer(\App\Models\Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $activeSubscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->latest()
            ->first();

        if (! $activeSubscription) {
            return back()->with('error', 'Customer has no active subscription. Please create a subscription first.');
        }

        $plans = \App\Models\Plan::where('isp_id', auth()->user()->isp_id)
            ->where('is_active', true)
            ->get();

        return view('isp.invoices.create', compact('customer', 'activeSubscription', 'plans'));
    }

    /**
     * Store a new invoice for a customer.
     */
    public function store(Request $request, \App\Models\Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $subscription = \App\Models\Subscription::where('id', $request->subscription_id)
            ->where('customer_id', $customer->id)
            ->with('plan')
            ->firstOrFail();

        if ($subscription->customer_id !== $customer->id || $subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $amount = (float) $request->amount;
        $lines = InvoiceTax::computeForAmount($amount, $customer->isp);

        Invoice::create([
            'isp_id' => $customer->isp_id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'amount' => $lines['amount'],
            'tax' => $lines['tax'],
            'total_amount' => $lines['total_amount'],
            'issue_date' => now(),
            'due_date' => $request->due_date,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return redirect()->route('isp.invoices.index')
            ->with('success', 'Invoice created successfully.');
    }
}
