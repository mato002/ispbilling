<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentCompletionService;
use App\Support\Export\CsvExporter;
use App\Support\InvoiceTax;
use App\Support\Search\SearchHelper;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use InteractsWithListings;

    public function __construct(protected PaymentCompletionService $paymentCompletionService) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = $this->filteredInvoicesQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.invoices.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.invoices.export.csv'));

        return view('isp.invoices.index', compact('invoices', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredInvoicesQuery($request, $ispId)->latest();
        $filename = 'invoices-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Invoice #', 'Customer', 'Phone', 'Status', 'Amount', 'Tax', 'Total',
            'Issue date', 'Due date', 'Paid at',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($invoices) use ($out) {
                foreach ($invoices as $invoice) {
                    fputcsv($out, [
                        $invoice->invoice_number,
                        $invoice->customer?->full_name,
                        $invoice->customer?->phone_number,
                        $invoice->status,
                        $invoice->amount,
                        $invoice->tax,
                        $invoice->total_amount,
                        $invoice->issue_date?->format('Y-m-d'),
                        $invoice->due_date?->format('Y-m-d'),
                        $invoice->paid_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredInvoicesQuery(Request $request, int $ispId): Builder
    {
        $query = Invoice::where('isp_id', $ispId)
            ->with(['customer', 'subscription']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('invoice_number', 'like', $pattern)
                    ->orWhereHas('customer', function ($customerQuery) use ($pattern) {
                        $customerQuery->where('full_name', 'like', $pattern)
                            ->orWhere('phone_number', 'like', $pattern);
                    });
            });
        }

        return $query;
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

    public function cancel(Invoice $invoice): RedirectResponse
    {
        if ($invoice->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be cancelled.');
        }

        if ($invoice->status === 'cancelled') {
            return back()->with('info', 'Invoice is already cancelled.');
        }

        if ($invoice->payments()->where('status', 'completed')->exists()) {
            return back()->with('error', 'Cannot cancel an invoice that already has a completed payment.');
        }

        $invoice->payments()
            ->where('status', 'pending')
            ->get()
            ->each(fn (Payment $payment) => $payment->markAsFailed());

        $invoice->cancel('Cancelled by '.auth()->user()->name);

        return redirect()
            ->route('isp.invoices.show', $invoice)
            ->with('success', 'Invoice cancelled.');
    }

    /**
     * Record cash / manual payment and complete the invoice immediately.
     */
    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Only pending or overdue invoices can be marked paid.');
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = DB::transaction(function () use ($invoice, $request) {
            $pending = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->where('payment_method', 'manual')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($pending) {
                $pending->markAsCompleted();

                return $pending->fresh();
            }

            $payment = Payment::create([
                'isp_id' => $invoice->isp_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'payment_method' => 'manual',
                'amount' => $invoice->total_amount,
                'phone_number' => $invoice->customer?->phone_number,
                'status' => 'pending',
                'metadata' => [
                    'recorded_by' => auth()->id(),
                    'cash_mark_paid' => true,
                    'notes' => $request->input('notes'),
                ],
            ]);

            $payment->markAsCompleted();

            return $payment->fresh();
        });

        $this->paymentCompletionService->processTenantPaymentSuccess($payment, true);

        return redirect()
            ->route('isp.invoices.show', $invoice)
            ->with('success', 'Invoice marked paid. Subscription/service updated if linked.');
    }
}
