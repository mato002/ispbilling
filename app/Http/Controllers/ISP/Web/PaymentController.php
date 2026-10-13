<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\PaymentCompletionService;
use App\Services\PaymentPostingService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use App\Support\Export\XlsxExporter;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected PaymentCompletionService $paymentCompletionService,
        protected PaymentPostingService $paymentPostingService,
    ) {}

    public function create(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $invoiceId = $request->get('invoice_id');
        $invoice = null;

        if ($invoiceId) {
            $invoice = \App\Models\Invoice::where('isp_id', $ispId)
                ->with('customer')
                ->findOrFail($invoiceId);
        }

        $invoices = \App\Models\Invoice::where('isp_id', $ispId)
            ->where('status', '!=', 'paid')
            ->with('customer')
            ->latest()
            ->get();

        return view('isp.payments.create', compact('invoice', 'invoices'));
    }

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = $this->filteredPaymentsQuery($request, $ispId)
            ->posted()
            ->where('status', 'completed');
        $perPage = $this->resolvePerPage($request);
        $payments = $query->latest('processed_at')->latest()->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.payments.index'), [
            'search' => 'Search',
            'payment_method' => [
                'label' => 'Method',
                'values' => [
                    'mpesa' => 'M-Pesa',
                    'selcom' => 'Selcom',
                    'manual' => 'Manual',
                    'voucher' => 'Voucher',
                ],
            ],
            'connection_type' => [
                'label' => 'Service',
                'values' => ['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE'],
            ],
            'date_from' => 'From',
            'date_to' => 'To',
        ]);

        $exportQuery = http_build_query($request->except('page'));
        $exportSuffix = $exportQuery !== '' ? '?'.$exportQuery : '';
        $exportFormats = [
            ['label' => 'Export CSV', 'url' => route('isp.payments.export.csv').$exportSuffix, 'download' => true],
            ['label' => 'Export Excel', 'url' => route('isp.payments.export.xlsx').$exportSuffix, 'download' => true],
            ['label' => 'Export PDF', 'url' => route('isp.payments.export.pdf').$exportSuffix, 'download' => true],
            ['label' => 'Reconciliation CSV', 'url' => route('isp.payments.reconciliation.csv').$exportSuffix, 'download' => true],
        ];

        return view('isp.payments.index', compact('payments', 'filterChips', 'exportFormats'));
    }

    public function unposted(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Payment::where('isp_id', $ispId)
            ->unposted()
            ->with(['assignedBy']);

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('payment_reference', 'like', $pattern)
                    ->orWhere('transaction_id', 'like', $pattern)
                    ->orWhere('phone_number', 'like', $pattern);
            });
        }

        $payments = $query->latest('processed_at')->latest()->paginate(20)->withQueryString();
        $customers = Customer::where('isp_id', $ispId)
            ->where('status', '!=', 'terminated')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone_number']);

        return view('isp.payments.unposted', compact('payments', 'customers'));
    }

    public function credits(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = Customer::where('isp_id', $ispId)
            ->where('account_credit', '>', 0);

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('full_name', 'like', $pattern)
                    ->orWhere('phone_number', 'like', $pattern);
            });
        }

        $customers = $query->orderByDesc('account_credit')->paginate(20)->withQueryString();

        return view('isp.payments.credits', compact('customers'));
    }

    public function failed(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = $this->failedPaymentsQuery($request, $ispId);

        $payments = $query->latest()->paginate(20)->withQueryString();
        $exportFormats = $this->listingExportFormats($request, route('isp.payments.failed.export.csv'));

        return view('isp.payments.failed', compact('payments', 'exportFormats'));
    }

    public function exportFailedCsv(Request $request): StreamedResponse
    {
        $query = $this->failedPaymentsQuery($request, (int) auth()->user()->isp_id)->latest();

        return CsvExporter::download('failed-payment-attempts-'.now()->format('Ymd-His').'.csv', [
            'Reference', 'Gateway transaction', 'Customer', 'Phone', 'Service', 'Method', 'Status',
            'Failure reason', 'Amount', 'Created at', 'Processed at',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($payments) use ($out) {
                foreach ($payments as $payment) {
                    fputcsv($out, [
                        $payment->payment_reference,
                        $payment->transaction_id,
                        $payment->customer?->full_name,
                        $payment->phone_number ?? $payment->customer?->phone_number,
                        $payment->serviceTypeLabel(),
                        $payment->payment_method,
                        $payment->status,
                        $payment->metadata['failure_reason'] ?? data_get($payment->metadata, 'stk_query_response.ResultDesc'),
                        $payment->amount,
                        $payment->created_at?->format('Y-m-d H:i:s'),
                        $payment->processed_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    private function failedPaymentsQuery(Request $request, int $ispId): Builder
    {
        $query = Payment::where('isp_id', $ispId)
            ->whereIn('status', ['pending', 'failed'])
            ->with(['customer', 'invoice']);

        if ($request->filled('connection_type')) {
            $this->applyConnectionTypeFilter($query, $request->string('connection_type')->toString());
        }
        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('payment_reference', 'like', $pattern)
                    ->orWhere('transaction_id', 'like', $pattern)
                    ->orWhere('phone_number', 'like', $pattern)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', $pattern));
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        return $query;
    }

    public function assignUnposted(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
        ]);

        $customer = Customer::query()->findOrFail($data['customer_id']);
        $invoice = isset($data['invoice_id']) ? \App\Models\Invoice::query()->find($data['invoice_id']) : null;

        try {
            $this->paymentPostingService->assignToCustomer(
                $payment,
                $customer,
                $invoice,
                auth()->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('isp.payments.show', $payment)
            ->with('success', 'Payment assigned and posted to customer account.');
    }

    public function forceSync(Payment $payment): RedirectResponse
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if ($payment->status === 'completed') {
            $this->paymentCompletionService->processTenantPaymentSuccess($payment->fresh(), true);

            return back()->with('success', 'Service automation re-run for this payment.');
        }

        return back()->with('error', 'Only completed payments can be force-synced. Check transaction logs for pending STK pushes.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredPaymentsQuery($request, $ispId)
            ->posted()->where('status', 'completed')->latest('processed_at')->latest();
        $filename = 'payments-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Reference', 'Transaction ID', 'Customer', 'Phone', 'Service', 'Invoice',
            'Method', 'Status', 'Amount', 'Processed at', 'Created',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($payments) use ($out) {
                foreach ($payments as $payment) {
                    fputcsv($out, [
                        $payment->payment_reference,
                        $payment->transaction_id,
                        $payment->customer?->full_name,
                        $payment->customer?->phone_number,
                        $payment->serviceTypeLabel(),
                        $payment->invoice?->invoice_number,
                        $payment->payment_method,
                        $payment->status,
                        $payment->amount,
                        $payment->processed_at?->format('Y-m-d H:i:s'),
                        $payment->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    public function exportXlsx(Request $request)
    {
        $query = $this->filteredPaymentsQuery($request, (int) auth()->user()->isp_id)
            ->posted()->where('status', 'completed')->with(['customer', 'invoice.subscription.plan'])
            ->reorder();

        return XlsxExporter::download(
            'payments-'.now()->format('Ymd-His').'.xlsx',
            ['Reference', 'Transaction ID', 'Customer', 'Phone', 'Service', 'Plan', 'Invoice', 'Method', 'Status', 'Amount', 'Processed at'],
            $this->paymentRows($query),
            'Payments'
        );
    }

    public function exportPdf(Request $request)
    {
        $query = $this->filteredPaymentsQuery($request, (int) auth()->user()->isp_id)
            ->posted()->where('status', 'completed')->with(['customer', 'invoice.subscription.plan'])
            ->latest('processed_at')->latest();
        $payments = $query->limit(500)->get();
        $isp = \App\Models\ISP::findOrFail(auth()->user()->isp_id);
        $rangeLabel = trim(($request->date_from ?: 'All dates').' to '.($request->date_to ?: 'Today'));

        return $this->renderPdf('isp.payments.statement-pdf', [
            'payments' => $payments,
            'isp' => $isp,
            'rangeLabel' => $rangeLabel,
            'total' => (float) $payments->sum('amount'),
            'exportedAt' => now(),
            'truncated' => $payments->count() === 500,
        ], 'payment-statement-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportReconciliationCsv(Request $request): StreamedResponse
    {
        $query = $this->filteredPaymentsQuery($request, (int) auth()->user()->isp_id)
            ->with(['customer.router', 'invoice.subscription.plan'])
            ->addSelect([
                'latest_stk_log_status' => \App\Models\PaymentTransactionLog::query()
                    ->select('status')->whereColumn('payment_id', 'payments.id')
                    ->whereIn('transaction_type', ['webhook', 'webhook_verification', 'status_query'])->latest('created_at')->limit(1),
                'latest_stk_log_error' => \App\Models\PaymentTransactionLog::query()
                    ->select('error_message')->whereColumn('payment_id', 'payments.id')
                    ->whereIn('transaction_type', ['webhook', 'webhook_verification', 'status_query'])->latest('created_at')->limit(1),
            ])->latest('created_at')->latest();

        return CsvExporter::download('payment-reconciliation-'.now()->format('Ymd-His').'.csv', [
            'Reference', 'Gateway transaction', 'Phone', 'Amount', 'Service', 'Plan', 'Router',
            'Payment status', 'Latest gateway callback/query status', 'Gateway error', 'Customer status', 'Subscription status', 'Provisioning automation run', 'Created at', 'Processed at',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($payments) use ($out) {
                foreach ($payments as $payment) {
                    $customer = $payment->customer;
                    $subscription = $payment->invoice?->subscription;
                    fputcsv($out, [
                        $payment->payment_reference,
                        $payment->transaction_id,
                        $payment->phone_number,
                        $payment->amount,
                        $payment->serviceTypeLabel(),
                        $subscription?->plan?->name,
                        $customer?->router?->name,
                        $payment->status,
                        $payment->latest_stk_log_status,
                        $payment->latest_stk_log_error,
                        $customer?->status,
                        $subscription?->status,
                        $payment->metadata['payment_success_side_effects_at'] ?? null,
                        $payment->created_at?->format('Y-m-d H:i:s'),
                        $payment->processed_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    public function receiptPdf(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) abort(403);
        if ($payment->status !== 'completed') abort(404, 'A receipt is available after payment is completed.');
        $payment->load(['isp', 'customer', 'invoice.subscription.plan']);

        return $this->renderPdf('isp.payments.receipt-pdf', [
            'payment' => $payment,
            'isp' => $payment->isp,
        ], 'receipt-'.$payment->payment_reference.'.pdf');
    }

    private function renderPdf(string $view, array $data, string $filename)
    {
        if (! class_exists(Dompdf::class)) abort(503, 'PDF export is unavailable on this server.');
        $pdf = new Dompdf;
        $pdf->loadHtml(view($view, $data)->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @return \Generator<int, array<int, scalar|null>> */
    private function paymentRows(Builder $query): \Generator
    {
        foreach ($query->lazyById(500) as $payment) {
            yield [
                $payment->payment_reference,
                $payment->transaction_id,
                $payment->customer?->full_name,
                $payment->customer?->phone_number,
                $payment->serviceTypeLabel(),
                $payment->invoice?->subscription?->plan?->name,
                $payment->invoice?->invoice_number,
                $payment->payment_method,
                $payment->status,
                (float) $payment->amount,
                $payment->processed_at?->format('Y-m-d H:i:s'),
            ];
        }
    }

    protected function filteredPaymentsQuery(Request $request, int $ispId): Builder
    {
        $query = Payment::where('isp_id', $ispId)
            ->with(['customer', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('posting_status')) {
            $query->where('posting_status', $request->posting_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('connection_type')) {
            $this->applyConnectionTypeFilter($query, $request->string('connection_type')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('processed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('processed_at', '<=', $request->date_to);
        }

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('payment_reference', 'like', $pattern)
                    ->orWhere('transaction_id', 'like', $pattern)
                    ->orWhereHas('customer', function ($customerQuery) use ($pattern) {
                        $customerQuery->where('full_name', 'like', $pattern)
                            ->orWhere('phone_number', 'like', $pattern);
                    });
            });
        }

        return $query;
    }

    private function applyConnectionTypeFilter(Builder $query, string $connectionType): void
    {
        if ($connectionType === 'hotspot') {
            $query->where(function (Builder $q) {
                $q->where('metadata->service_type', 'hotspot')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereNull('metadata->service_type')
                            ->whereHas('customer', fn (Builder $customer) => $customer->where('connection_type', 'hotspot'));
                    })
                    ->orWhereNotNull('voucher_id');
            });
        } elseif ($connectionType === 'pppoe') {
            $query->where(function (Builder $q) {
                $q->where('metadata->service_type', 'pppoe')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereNull('metadata->service_type')
                            ->whereHas('customer', fn (Builder $customer) => $customer->where('connection_type', 'pppoe'));
                    });
            });
        }
    }

    /**
     * Confirm or reject a pending manual payment from the ISP web console.
     */
    public function confirmManual(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $data = $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be confirmed or rejected.');
        }

        if ($data['confirm']) {
            DB::transaction(function () use ($payment) {
                $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                if ($locked->status !== 'completed') {
                    $locked->markAsCompleted();
                }
            });

            $this->paymentCompletionService->processTenantPaymentSuccess($payment->fresh(), true);

            return redirect()
                ->route('isp.payments.show', $payment)
                ->with('success', 'Payment marked completed. Customer service updated.');
        }

        $payment->markAsFailed();

        return redirect()
            ->route('isp.payments.show', $payment)
            ->with('success', 'Payment marked as failed.');
    }

    /**
     * Manually activate internet access for this payment's customer.
     * Intended for completed manual (cash) payments.
     */
    public function activateInternet(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $customer = $payment->customer;

        if (! $customer || ! $customer->router) {
            return back()->with('error', 'Customer or router not found for this payment.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);

            if ($customer->connection_type === 'hotspot') {
                $mikrotik->activateHotspotUser($customer);
            } else {
                $mikrotik->enableUser($customer);
            }

            $customer->activate();

            // Log manual activation action
            \App\Services\ActivityLogService::logAction(
                'manual_activation',
                "Manually activated internet for customer {$customer->full_name} (ID: {$customer->id}) via payment {$payment->payment_reference}",
                [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'connection_type' => $customer->connection_type,
                ]
            );

            return back()->with('success', 'Internet activated manually for this customer.');
        } catch (\Exception $e) {
            // Log failed activation
            \App\Services\ActivityLogService::logAction(
                'manual_activation',
                "Failed to manually activate internet for customer {$customer->full_name} (ID: {$customer->id}) via payment {$payment->payment_reference}: ".$e->getMessage(),
                [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );

            return back()->with('error', 'Failed to activate internet: '.$e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $payment->load(['customer', 'invoice', 'assignedBy']);

        return view('isp.payments.show', compact('payment'));
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|in:mpesa,selcom,manual',
            'phone_number' => 'required_if:payment_method,mpesa,selcom',
        ]);

        $wantsJson = $request->wantsJson() || $request->ajax();

        $invoice = \App\Models\Invoice::with('customer', 'isp')->findOrFail($request->invoice_id);

        if ($invoice->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if ($invoice->status === 'paid') {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'This invoice has already been paid.'], 422);
            }

            return back()->with('error', 'This invoice has already been paid.');
        }

        if ($invoice->status === 'cancelled') {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Cancelled invoices cannot accept payments.'], 422);
            }
            return back()->with('error', 'Cancelled invoices cannot accept payments.');
        }

        $phoneNumber = $request->phone_number ?? $invoice->customer->phone_number;
        if (in_array($request->payment_method, ['mpesa', 'selcom'], true)) {
            $phoneNumber = preg_replace('/^0/', '254', (string) $phoneNumber);
            $phoneNumber = preg_replace('/[^0-9]/', '', (string) $phoneNumber);

            if ($request->payment_method === 'mpesa' && ! preg_match('/^254[0-9]{9}$/', $phoneNumber)) {
                if ($wantsJson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid phone number format. Please use format: 254XXXXXXXXX',
                    ], 422);
                }

                return back()->with('error', 'Invalid phone number format. Please use format: 254XXXXXXXXX');
            }
        }

        $payment = \App\Models\Payment::create([
            'isp_id' => $invoice->isp_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'payment_method' => $request->payment_method,
            'amount' => $invoice->total_amount,
            'phone_number' => $phoneNumber ?? $invoice->customer->phone_number,
            'status' => 'pending',
        ]);

        \App\Models\PaymentTransactionLog::log([
            'payment_id' => $payment->id,
            'isp_id' => $payment->isp_id,
            'transaction_type' => 'initiated',
            'payment_method' => $request->payment_method,
            'payment_reference' => $payment->payment_reference,
            'status' => 'pending',
            'amount' => $payment->amount,
            'request_data' => $request->all(),
        ]);

        if ($request->payment_method === 'mpesa') {
            $mpesaService = new \App\Services\MpesaService($invoice->isp);
            $result = $mpesaService->initiateStkPush($payment);

            if ($result['success']) {
                if ($wantsJson) {
                    return response()->json([
                        'success' => true,
                        'message' => 'MPesa STK Push initiated. Please check your phone to complete the payment.',
                        'payment_id' => $payment->id,
                        'redirect' => route('isp.payments.show', $payment),
                    ]);
                }

                return redirect()->route('isp.payments.show', $payment)
                    ->with('success', 'MPesa STK Push initiated. Please check your phone to complete the payment.');
            }

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initiate MPesa payment.',
                ], 400);
            }

            return back()->with('error', $result['message'] ?? 'Failed to initiate MPesa payment.');
        }

        if ($request->payment_method === 'selcom') {
            $selcomService = new \App\Services\SelcomService($invoice->isp);
            $result = $selcomService->initiatePayment($payment);

            if ($result['success']) {
                if ($wantsJson) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'] ?? 'Selcom payment initiated.',
                        'payment_id' => $payment->id,
                        'payment_url' => $result['payment_url'] ?? null,
                        'redirect' => $result['payment_url'] ?? route('isp.payments.show', $payment),
                    ]);
                }

                if (! empty($result['payment_url'])) {
                    return redirect()->away($result['payment_url']);
                }

                return redirect()->route('isp.payments.show', $payment)
                    ->with('success', 'Selcom payment initiated. Awaiting confirmation.');
            }

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initiate Selcom payment.',
                ], 400);
            }

            return back()->with('error', $result['message'] ?? 'Failed to initiate Selcom payment.');
        }

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded. Awaiting confirmation.',
                'payment_id' => $payment->id,
                'redirect' => route('isp.payments.show', $payment),
            ]);
        }

        return redirect()->route('isp.payments.show', $payment)
            ->with('success', 'Payment recorded. Awaiting confirmation.');
    }

    public function checkStatus(Payment $payment)
    {
        if ($payment->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        return response()->json([
            'status' => $payment->status,
            'payment_reference' => $payment->payment_reference,
            'transaction_id' => $payment->transaction_id,
            'processed_at' => $payment->processed_at?->toIso8601String(),
        ]);
    }

    public function transactionLogs(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = \App\Models\PaymentTransactionLog::where('isp_id', $ispId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(20)->appends($request->query());

        return view('isp.payments.transaction-logs', compact('logs'));
    }

    public function showTransactionLog(\App\Models\PaymentTransactionLog $log)
    {
        if ($log->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        return response()->json([
            'transaction_type' => $log->transaction_type,
            'status' => $log->status,
            'amount' => $log->amount,
            'ip_address' => $log->ip_address,
            'error_message' => $log->error_message,
            'request_data' => $log->request_data,
            'response_data' => $log->response_data,
        ]);
    }

    /**
     * Show payments for a specific customer
     */
    public function customerPayments(\App\Models\Customer $customer, Request $request)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $query = Payment::where('customer_id', $customer->id)
            ->with('invoice');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest('processed_at')->latest()->paginate(20)->appends($request->query());

        return view('isp.payments.customer', compact('customer', 'payments'));
    }
}
