<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\ISP\BaseController;
use App\Models\Customer;
use App\Models\DataUsage;
use App\Models\Router;
use App\Services\DataUsageMonitoringService;
use App\Support\Export\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataUsageController extends BaseController
{
    use InteractsWithListings;

    public function __construct(
        protected DataUsageMonitoringService $monitoring,
    ) {}

    public function index(Request $request): View
    {
        $ispId = $this->getIspId();
        $perPage = $this->resolvePerPage($request, 25);

        $summaryQuery = $this->monitoring->customerUsageQuery($ispId, $request);
        $paginated = $this->paginateCustomerSummaries($summaryQuery, $perPage, $request);

        $routers = Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name']);
        $routerLabels = $routers->pluck('name', 'id')->mapWithKeys(
            fn (string $name, int $id): array => [(string) $id => $name]
        )->all();

        $customers = Customer::forIsp($ispId)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);

        $stats = $this->monitoring->workspaceStats($ispId, $request);
        $routerSummaries = $this->monitoring->routerSummaries($ispId, $request);

        $filterChips = $this->listingFilterChips($request, route('isp.data-usage.index'), [
            'search' => 'Search',
            'customer_id' => [
                'label' => 'Customer',
                'values' => $customers->pluck('full_name', 'id')->mapWithKeys(
                    fn (string $name, int $id): array => [(string) $id => $name]
                )->all(),
            ],
            'router_id' => [
                'label' => 'Router',
                'values' => $routerLabels,
            ],
            'package_type' => [
                'label' => 'Package type',
                'values' => $this->monitoring->packageTypeFilterOptions(),
            ],
            'date_from' => 'From',
            'date_to' => 'To',
            'sort' => [
                'label' => 'Sort',
                'values' => [
                    'usage_desc' => 'Highest usage',
                    'usage_asc' => 'Lowest usage',
                    'last_active' => 'Last active',
                ],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.data-usage.export.csv'));

        return view('isp.data-usage.index', compact(
            'paginated',
            'routers',
            'customers',
            'stats',
            'routerSummaries',
            'filterChips',
            'exportFormats',
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = $this->getIspId();
        $query = $this->monitoring->customerUsageQuery($ispId, $request);
        $filename = 'data-usage-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Customer', 'Username', 'Connection', 'Upload', 'Download', 'Total', 'Last active',
        ], function ($out) use ($query) {
            $query->chunk(100, function ($rows) use ($out) {
                $customerIds = $rows->pluck('customer_id');
                $customers = Customer::whereIn('id', $customerIds)->get()->keyBy('id');

                foreach ($rows as $row) {
                    $customer = $customers->get($row->customer_id);
                    fputcsv($out, [
                        $customer?->full_name,
                        $customer?->username,
                        $customer?->connection_type,
                        $row->upload_bytes,
                        $row->download_bytes,
                        $row->total_bytes,
                        $row->last_active_at,
                    ]);
                }
            });
        });
    }

    public function customer(Request $request, Customer $customer): View
    {
        $this->ensureIspAccess($customer);
        $breakdown = $this->monitoring->customerBreakdown($customer);
        $recentRecords = DataUsage::query()
            ->where('customer_id', $customer->id)
            ->with(['router', 'plan'])
            ->latest('recorded_at')
            ->limit(50)
            ->get();

        return view('isp.data-usage.customer', compact('customer', 'breakdown', 'recentRecords'));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\DataUsage>  $query
     */
    protected function paginateCustomerSummaries($query, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->get()->count();
        $rows = $query->forPage($page, $perPage)->get();

        $customerIds = $rows->pluck('customer_id');
        $customers = Customer::whereIn('id', $customerIds)
            ->with(['router', 'subscriptions' => fn ($q) => $q->with('plan')->where('status', 'active')->latest()])
            ->get()
            ->keyBy('id');

        $items = $rows->map(function ($row) use ($customers) {
            $row->customer = $customers->get($row->customer_id);

            return $row;
        });

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
