<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ISP;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isps = ISP::orderBy('name')->get(['id', 'name']);
        $ispValues = $isps->mapWithKeys(fn ($isp) => [(string) $isp->id => $isp->name])->all();

        $query = $this->filteredCustomersQuery($request);
        $perPage = $this->resolvePerPage($request, 20);
        $customers = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.customers.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'expired' => 'Expired',
                ],
            ],
            'isp_id' => [
                'label' => 'ISP',
                'values' => $ispValues,
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.customers.export.csv'));

        return view('admin.customers.index', compact('customers', 'isps', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = $this->filteredCustomersQuery($request);

        return CsvExporter::download(
            'customers-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Name', 'Phone', 'Username', 'ISP', 'Connection', 'Status', 'Location', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($customers) use ($out) {
                    foreach ($customers as $customer) {
                        fputcsv($out, [
                            $customer->id,
                            $customer->full_name,
                            $customer->phone_number,
                            $customer->username,
                            $customer->isp?->name,
                            $customer->connection_type,
                            $customer->status,
                            $customer->location,
                            $customer->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredCustomersQuery(Request $request)
    {
        $query = Customer::with(['isp', 'router', 'subscriptions.plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('isp_id')) {
            $query->where('isp_id', $request->isp_id);
        }

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('full_name', 'like', $pattern)
                    ->orWhere('phone_number', 'like', $pattern)
                    ->orWhere('username', 'like', $pattern);
            });
        }

        return $query->latest();
    }
}
