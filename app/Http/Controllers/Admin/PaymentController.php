<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Payment;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isps = ISP::orderBy('name')->get(['id', 'name']);
        $ispValues = $isps->mapWithKeys(fn ($isp) => [(string) $isp->id => $isp->name])->all();

        $query = $this->filteredPaymentsQuery($request);
        $perPage = $this->resolvePerPage($request, 20);
        $payments = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.payments.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'completed' => 'Completed',
                    'pending' => 'Pending',
                    'failed' => 'Failed',
                ],
            ],
            'payment_method' => [
                'label' => 'Method',
                'values' => [
                    'mpesa' => 'MPesa',
                    'selcom' => 'Selcom',
                    'manual' => 'Manual',
                ],
            ],
            'isp_id' => [
                'label' => 'ISP',
                'values' => $ispValues,
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.payments.export.csv'));

        return view('admin.payments.index', compact('payments', 'isps', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = $this->filteredPaymentsQuery($request);

        return CsvExporter::download(
            'payments-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Reference', 'Customer', 'Phone', 'ISP', 'Amount', 'Currency', 'Method', 'Status', 'Processed', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($payments) use ($out) {
                    foreach ($payments as $payment) {
                        fputcsv($out, [
                            $payment->id,
                            $payment->payment_reference,
                            $payment->customer?->full_name,
                            $payment->customer?->phone_number,
                            $payment->isp?->name,
                            $payment->amount,
                            $payment->isp?->currency,
                            $payment->payment_method,
                            $payment->status,
                            $payment->processed_at?->format('Y-m-d H:i:s'),
                            $payment->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredPaymentsQuery(Request $request)
    {
        $query = Payment::with(['isp', 'customer', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('isp_id')) {
            $query->where('isp_id', $request->isp_id);
        }

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('payment_reference', 'like', $pattern)
                    ->orWhereHas('customer', function ($customerQuery) use ($pattern) {
                        $customerQuery->where('full_name', 'like', $pattern)
                            ->orWhere('phone_number', 'like', $pattern);
                    });
            });
        }

        return $query->latest();
    }
}
