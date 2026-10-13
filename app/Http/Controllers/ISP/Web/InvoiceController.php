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

        if ($request->header('X-Listing-Refresh') === '1') {
            return response()->json([
                'rows' => view('isp.invoices.partials.rows', compact('invoices'))->render(),
                'pagination' => view('isp.invoices.partials.pagination', compact('invoices'))->render(),
                'from' => $invoices->firstItem() ?? 0,
                'to' => $invoices->lastItem() ?? 0,
                'total' => $invoices->total(),
            ]);
        }

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

        $invoiceCustomers = \App\Models\Customer::query()
            ->where('isp_id', $ispId)
            ->whereHas('subscriptions', function ($subscriptionQuery) {
                $subscriptionQuery->where('status', 'active')
                    ->where('end_date', '>=', now());
            })
            ->with(['subscriptions' => function ($subscriptionQuery) {
                $subscriptionQuery->where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->with('plan')
                    ->latest();
            }])
            ->orderBy('full_name')
            ->get();

        return view('isp.invoices.index', compact('invoices', 'filterChips', 'exportFormats', 'invoiceCustomers'));
    }

    /** Create an invoice from the invoice-list modal. */
    public function storeFromIndex(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ispId = auth()->user()->isp_id;
        $customer = \App\Models\Customer::query()
            ->where('isp_id', $ispId)
            ->findOrFail($validated['customer_id']);
        $subscription = \App\Models\Subscription::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->findOrFail($validated['subscription_id']);

        $lines = InvoiceTax::computeForAmount((float) $validated['amount'], $customer->isp);

        Invoice::create([
            'isp_id' => $ispId,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'amount' => $lines['amount'],
            'tax' => $lines['tax'],
            'total_amount' => $lines['total_amount'],
            'issue_date' => now(),
            'due_date' => $validated['due_date'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('isp.invoices.index')->with('success', 'Invoice created successfully.');
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
        $invoice->load(['customer', 'subscription', 'payments', 'isp', 'adjustments.actor', 'auditLogs.user']);

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
        \App\Models\PaymentAuditLog::log([
            'isp_id' => $invoice->isp_id, 'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id, 'user_id' => auth()->id(),
            'action' => 'invoice_cancelled', 'status' => 'success',
            'description' => 'Invoice cancelled by '.auth()->user()->name,
        ]);

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
            'payment_method' => ['nullable', 'in:cash,bank_transfer,offline_mpesa'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
        ]);

        $payment = DB::transaction(function () use ($invoice, $request) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($lockedInvoice->status, ['pending', 'overdue'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['invoice' => 'Only pending or overdue invoices can be marked paid.']);
            }
            $pending = Payment::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('status', 'pending')
                ->where('payment_method', 'manual')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($pending) {
                $pending->update([
                    'amount' => $lockedInvoice->total_amount,
                    'payment_method' => match ($request->input('payment_method', 'cash')) {
                        'bank_transfer' => 'bank_transfer', 'offline_mpesa' => 'mpesa', default => 'manual',
                    },
                    'transaction_id' => $request->input('transaction_id') ?: $pending->transaction_id,
                    'metadata' => array_merge($pending->metadata ?? [], [
                        'recorded_method' => $request->input('payment_method', 'cash'),
                        'external_reference' => $request->input('transaction_id'),
                        'notes' => $request->input('notes'),
                        'recorded_by' => auth()->id(),
                        'cash_mark_paid' => true,
                    ]),
                ]);
                $pending->markAsCompleted();

                return $pending->fresh();
            }

            $payment = Payment::create([
                'isp_id' => $lockedInvoice->isp_id,
                'customer_id' => $lockedInvoice->customer_id,
                'invoice_id' => $lockedInvoice->id,
                'payment_method' => match ($request->input('payment_method', 'cash')) {
                    'bank_transfer' => 'bank_transfer', 'offline_mpesa' => 'mpesa', default => 'manual',
                },
                'amount' => $lockedInvoice->total_amount,
                'transaction_id' => $request->input('transaction_id'),
                'phone_number' => $lockedInvoice->customer?->phone_number,
                'status' => 'pending',
                'metadata' => [
                    'recorded_by' => auth()->id(),
                    'cash_mark_paid' => true,
                    'recorded_method' => $request->input('payment_method', 'cash'),
                    'external_reference' => $request->input('transaction_id'),
                    'notes' => $request->input('notes'),
                ],
            ]);

            $payment->markAsCompleted();

            return $payment->fresh();
        });

        $this->paymentCompletionService->processTenantPaymentSuccess($payment, true);

        \App\Models\PaymentAuditLog::log([
            'isp_id' => $invoice->isp_id,
            'payment_id' => $payment->id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'action' => 'manual_payment_recorded',
            'payment_method' => in_array($request->input('payment_method'), ['bank_transfer', 'offline_mpesa'], true)
                ? ($request->input('payment_method') === 'bank_transfer' ? 'bank_transfer' : 'mpesa')
                : 'manual',
            'amount' => $payment->amount,
            'transaction_id' => $request->input('transaction_id'),
            'status' => 'success',
            'description' => 'Manual invoice payment recorded by '.auth()->user()->name,
        ]);

        return redirect()
            ->route('isp.invoices.show', $invoice)
            ->with('success', 'Invoice marked paid. Subscription/service updated if linked.');
    }

    public function sendReminder(Invoice $invoice): RedirectResponse
    {
        $this->assertInvoiceTenant($invoice);
        if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Only unpaid invoices can receive a reminder.');
        }
        $customer = $invoice->customer;
        if (! $customer || (! $customer->phone_number && ! $customer->email)) {
            return back()->with('error', 'This customer has no phone number or email address.');
        }

        try {
            $notification = new \App\Notifications\PaymentReminder($invoice->load(['isp', 'customer']));
            \Illuminate\Support\Facades\Notification::sendNow($customer, $notification);
            $channels = collect($notification->via($customer))->map(function ($channel) {
                if ($channel === 'mail') return 'Email';
                if (is_string($channel) && str_contains($channel, 'DynamicSmsChannel')) return 'SMS';
                return is_string($channel) ? class_basename($channel) : 'notification';
            })->implode(' and ');
            \App\Models\PaymentAuditLog::log([
                'isp_id' => $invoice->isp_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'reminder_sent',
                'status' => 'success',
                'description' => 'Reminder sent via '.$channels,
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice reminder delivery failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Reminder could not be delivered. Check notification settings and try again.');
        }

        return back()->with('success', 'Payment reminder sent to '.$customer->phone_number.' / '.$customer->email.'.');
    }

    public function adjust(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->assertInvoiceTenant($invoice);
        if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Only unpaid invoices can be adjusted.');
        }
        $data = $request->validate([
            'type' => ['required', 'in:credit,discount'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($locked->status, ['pending', 'overdue'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['invoice' => 'Invoice is no longer unpaid.']);
            }
            $before = round((float) $locked->total_amount, 2);
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > $before) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Adjustment must be greater than zero and no more than the balance due.']);
            }
            if ($data['type'] === 'credit') {
                $customer = \App\Models\Customer::query()->lockForUpdate()->findOrFail($locked->customer_id);
                if ((float) $customer->account_credit < $amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Customer account credit is only KES '.number_format((float) $customer->account_credit, 2).'.',
                    ]);
                }
                $customer->decrement('account_credit', $amount);
            }
            $after = round($before - $amount, 2);
            $locked->update([
                'total_amount' => $after,
                ...($after === 0.0 ? ['status' => 'paid', 'paid_date' => now()] : []),
            ]);
            $locked->adjustments()->create([
                'user_id' => auth()->id(), 'type' => $data['type'], 'amount' => $amount,
                'before_total' => $before, 'after_total' => $after, 'reason' => $data['reason'],
            ]);
            \App\Models\PaymentAuditLog::log([
                'isp_id' => $locked->isp_id, 'customer_id' => $locked->customer_id,
                'invoice_id' => $locked->id, 'user_id' => auth()->id(),
                'action' => 'invoice_'.$data['type'], 'status' => 'success', 'amount' => $amount,
                'description' => ucfirst($data['type']).' applied'.($data['type'] === 'credit' ? ' from customer account credit' : '').'. Balance changed from '.$before.' to '.$after.'. '.$data['reason'],
            ]);
        });

        return back()->with('success', 'Invoice balance adjusted.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->assertInvoiceTenant($invoice);
        if ($invoice->status === 'paid' || $invoice->payments()->where('status', 'completed')->exists()) {
            return back()->with('error', 'Invoices with completed payments cannot be deleted.');
        }
        DB::transaction(function () use ($invoice) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $locked->payments()->where('status', 'pending')->get()->each(fn (Payment $payment) => $payment->markAsFailed());
            \App\Models\PaymentAuditLog::log([
                'isp_id' => $locked->isp_id, 'customer_id' => $locked->customer_id,
                'invoice_id' => $locked->id, 'user_id' => auth()->id(), 'action' => 'invoice_deleted',
                'status' => 'success', 'amount' => $locked->total_amount,
                'description' => 'Unpaid invoice deleted by '.auth()->user()->name.'; invoice number '.$locked->invoice_number,
            ]);
            $locked->delete();
        });

        return redirect()->route('isp.invoices.index')->with('success', 'Unpaid invoice deleted. Its audit record was retained.');
    }

    private function assertInvoiceTenant(Invoice $invoice): void
    {
        abort_unless($invoice->isp_id === auth()->user()->isp_id, 403);
    }
}
