<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ISP;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\Export\CsvExporter;
use App\Services\IspReportBuilder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function builder(Request $request, IspReportBuilder $builder)
    {
        $definitions = $builder->definitions();
        $request->merge([
            'dataset' => $request->input('dataset', 'payments'),
            'metric' => $request->input('metric', 'count'),
            'mode' => $request->input('mode', 'rows'),
        ]);
        $validated = $request->validate([
            'dataset' => ['required', Rule::in(array_keys($definitions))],
            'columns' => ['nullable', 'array', 'max:12'],
            'columns.*' => ['string'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'group_by' => ['nullable', 'string'],
            'metric' => ['required', Rule::in(['count', 'sum', 'average'])],
            'measure' => ['nullable', 'string'],
            'mode' => ['required', Rule::in(['rows', 'aggregate'])],
        ]);
        $dataset = $definitions[$validated['dataset']];
        $columnKeys = array_keys($dataset['columns']);
        $validated['columns'] = array_values(array_intersect($validated['columns'] ?? array_slice($columnKeys, 0, 6), $columnKeys));
        if ($validated['columns'] === [] && empty($validated['group_by'])) $validated['columns'] = array_slice($columnKeys, 0, 1);
        if (! empty($validated['status']) && ! in_array($validated['status'], $dataset['status'], true)) {
            return back()->withErrors(['status' => 'Choose a valid status for this report.'])->withInput();
        }
        $groupable = array_keys(array_filter($dataset['columns'], fn ($field) => ! empty($field['group'])));
        if (! empty($validated['group_by']) && ! in_array($validated['group_by'], $groupable, true)) {
            return back()->withErrors(['group_by' => 'Choose a valid grouping field.'])->withInput();
        }
        if (in_array($validated['metric'], ['sum', 'average'], true) && ! isset($dataset['measures'][$validated['measure'] ?? ''])) {
            return back()->withErrors(['measure' => 'Choose a valid numeric field for this calculation.'])->withInput();
        }

        $results = null;
        $records = null;
        $selectedColumns = [];
        if ($request->has('run')) {
            if ($validated['mode'] === 'aggregate') {
                $metricLabel = $validated['metric'] === 'count'
                    ? 'Record count'
                    : ucfirst($validated['metric']).' '.$dataset['columns'][$validated['measure']]['label'];
                $validated['metric_label'] = $metricLabel;
                $results = $builder->query((int) auth()->user()->isp_id, $validated);
            } else {
                [$query, $selectedColumns] = $builder->rows((int) auth()->user()->isp_id, $validated);
                $records = $query->paginate(50)->withQueryString();
            }
        }

        return view('isp.reports.builder', compact('definitions', 'dataset', 'validated', 'results', 'records', 'selectedColumns'));
    }

    public function exportBuilder(Request $request, IspReportBuilder $builder): StreamedResponse
    {
        $definitions = $builder->definitions();
        $validated = $request->validate([
            'dataset' => ['required', Rule::in(array_keys($definitions))],
            'columns' => ['nullable', 'array', 'max:12'],
            'columns.*' => ['string'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'group_by' => ['nullable', 'string'],
            'metric' => ['required', Rule::in(['count', 'sum', 'average'])],
            'measure' => ['nullable', 'string'],
            'mode' => ['required', Rule::in(['rows', 'aggregate'])],
        ]);
        $dataset = $definitions[$validated['dataset']];
        $keys = array_keys($dataset['columns']);
        $validated['columns'] = array_values(array_intersect($validated['columns'] ?? array_slice($keys, 0, 6), $keys));
        if ($validated['columns'] === []) $validated['columns'] = array_slice($keys, 0, 1);
        if (! empty($validated['status']) && ! in_array($validated['status'], $dataset['status'], true)) abort(422);
        $groupable = array_keys(array_filter($dataset['columns'], fn ($field) => ! empty($field['group'])));
        if (! empty($validated['group_by']) && ! in_array($validated['group_by'], $groupable, true)) abort(422);
        if (in_array($validated['metric'], ['sum', 'average'], true) && ! isset($dataset['measures'][$validated['measure'] ?? ''])) abort(422);
        if ($validated['mode'] === 'aggregate') {
            $metricLabel = $validated['metric'] === 'count' ? 'Record count' : ucfirst($validated['metric']).' '.$dataset['columns'][$validated['measure']]['label'];
            $validated['metric_label'] = $metricLabel;
            $results = $builder->query((int) auth()->user()->isp_id, $validated);
            return CsvExporter::download('custom-'.$validated['dataset'].'-summary-'.now()->format('Ymd-His').'.csv',
                [$validated['group_by'] ? $dataset['columns'][$validated['group_by']]['label'] : 'Total', $metricLabel],
                function ($out) use ($results) {
                    foreach ($results as [$label, $value]) {
                        if (is_string($label) && preg_match('/^\s*[=+\-@]/u', $label)) $label = "'".$label;
                        fputcsv($out, [$label, $value]);
                    }
                });
        }
        [$query, $selected] = $builder->rows((int) auth()->user()->isp_id, $validated);

        return CsvExporter::download('custom-'.$validated['dataset'].'-'.now()->format('Ymd-His').'.csv',
            array_map(fn ($field) => $field['label'], array_values($selected)),
            function ($out) use ($query, $selected) {
                $query->chunk(500, function ($rows) use ($out, $selected) {
                    foreach ($rows as $row) {
                        $values = [];
                        foreach (array_keys($selected) as $key) {
                            $value = $row->{$key};
                            if (is_string($value) && preg_match('/^\s*[=+\-@]/u', $value)) $value = "'".$value;
                            $values[] = $value;
                        }
                        fputcsv($out, $values);
                    }
                });
            });
    }

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $isp = ISP::query()->with('saasPlan')->findOrFail($ispId);
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $paymentsInRange = Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end]);

        $stats = [
            'total_customers' => Customer::where('isp_id', $ispId)->count(),
            'active_customers' => Customer::where('isp_id', $ispId)->where('status', 'active')->count(),
            'suspended_customers' => Customer::where('isp_id', $ispId)->where('status', 'suspended')->count(),
            'new_customers' => Customer::where('isp_id', $ispId)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'total_revenue' => (float) (clone $paymentsInRange)->sum('amount'),
            'payment_count' => (clone $paymentsInRange)->count(),
            'pending_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('isp_id', $ispId)->where('status', 'overdue')->count(),
            'active_subscriptions' => Subscription::whereHas('customer', function ($q) use ($ispId) {
                $q->where('isp_id', $ispId);
            })->where('status', 'active')->count(),
        ];

        $rangeDays = max(1, $start->diffInDays($end) + 1);
        $previousStart = $start->copy()->subDays($rangeDays);
        $previousEnd = $start->copy()->subSecond();
        $previousRevenue = (float) Payment::query()
            ->where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$previousStart, $previousEnd])
            ->sum('amount');

        $stats['revenue_change_pct'] = $previousRevenue > 0
            ? (($stats['total_revenue'] - $previousRevenue) / $previousRevenue) * 100
            : ($stats['total_revenue'] > 0 ? 100.0 : 0.0);

        $saasAdvancedAnalytics = $isp->saasHasFeature('advanced_analytics');
        $focus = $request->get('focus');
        $reportView = match ($focus) {
            'revenue-per-plan' => 'revenue',
            'monthly-collections' => 'collections',
            'customer-growth' => 'growth',
            default => 'overview',
        };

        $reportMeta = match ($reportView) {
            'revenue' => [
                'title' => 'Revenue per plan',
                'description' => 'Completed payments attributed through invoice → subscription → plan.',
            ],
            'collections' => [
                'title' => 'Monthly collections',
                'description' => 'Completed payment totals by month.',
            ],
            'growth' => [
                'title' => 'Customer growth',
                'description' => 'New customers per month and current status mix.',
            ],
            default => [
                'title' => 'Reports overview',
                'description' => 'Revenue, collections, and customer growth for the selected period.',
            ],
        };

        return view('isp.reports.index', compact(
            'stats',
            'startDate',
            'endDate',
            'saasAdvancedAnalytics',
            'focus',
            'reportView',
            'reportMeta'
        ));
    }

    protected function wantsReportJson(Request $request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->query('format') === 'json'
            || str_contains((string) $request->header('Accept'), 'application/json');
    }

    protected function redirectToReportFocus(string $focus)
    {
        return redirect(route('isp.reports.index', ['focus' => $focus]).'#'.$focus);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCustomerGrowthPayload(int $ispId, int $months = 12): array
    {
        $months = max(1, min(36, $months));
        $start = now()->subMonths($months - 1)->startOfMonth();
        $end = now()->endOfMonth();

        $statusRows = Customer::where('isp_id', $ispId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $statusColors = [
            'active' => '#10b981',
            'suspended' => '#ef4444',
            'expired' => '#f59e0b',
            'inactive' => '#6b7280',
        ];

        $statusLabels = [];
        $statusValues = [];
        $statusBg = [];
        foreach ($statusRows as $item) {
            $statusLabels[] = ucfirst((string) $item->status);
            $statusValues[] = (int) $item->count;
            $statusBg[] = $statusColors[strtolower((string) $item->status)] ?? '#6b7280';
        }

        $growthRows = Customer::where('isp_id', $ispId)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $growthLabels = [];
        $growthValues = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $growthLabels[] = $cursor->format('M Y');
            $growthValues[] = (int) ($growthRows[$key] ?? 0);
            $cursor->addMonth();
        }

        $active = (int) ($statusRows->firstWhere('status', 'active')->count ?? 0);
        $suspended = (int) ($statusRows->firstWhere('status', 'suspended')->count ?? 0);
        $peakValue = $growthValues ? max($growthValues) : 0;
        $peakIndex = $growthValues ? array_search($peakValue, $growthValues, true) : false;

        return [
            'labels' => $statusLabels,
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => $statusValues,
                    'backgroundColor' => $statusBg,
                ],
            ],
            'summary' => [
                'total' => array_sum($statusValues),
                'active' => $active,
                'suspended' => $suspended,
                'new_in_period' => array_sum($growthValues),
                'peak_month' => $peakIndex === false ? null : $growthLabels[$peakIndex],
            ],
            'growth' => [
                'labels' => $growthLabels,
                'datasets' => [
                    [
                        'label' => 'New customers',
                        'data' => $growthValues,
                        'borderColor' => '#6366f1',
                        'backgroundColor' => 'rgba(99, 102, 241, 0.25)',
                        'fill' => true,
                        'tension' => 0.35,
                    ],
                ],
                'details' => array_map(
                    fn ($label, $count) => ['month' => $label, 'new_customers' => $count],
                    $growthLabels,
                    $growthValues
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRevenuePerPlanPayload(int $ispId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $data = Payment::where('payments.isp_id', $ispId)
            ->where('payments.status', 'completed')
            ->whereBetween(DB::raw('COALESCE(payments.processed_at, payments.created_at)'), [$start, $end])
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('subscriptions', 'invoices.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                'plans.id',
                'plans.name',
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT payments.id) as payment_count'),
                DB::raw('COUNT(DISTINCT subscriptions.customer_id) as customer_count')
            )
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('total_revenue')
            ->get();

        $labels = $data->pluck('name')->all();
        $revenue = $data->map(fn ($item) => (float) $item->total_revenue)->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenue,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ],
            ],
            'summary' => [
                'total_revenue' => array_sum($revenue),
                'total_payments' => (int) $data->sum('payment_count'),
                'total_customers' => (int) $data->sum('customer_count'),
            ],
            'details' => $data->map(function ($item) {
                return [
                    'plan_id' => $item->id,
                    'plan_name' => $item->name,
                    'revenue' => (float) $item->total_revenue,
                    'payment_count' => (int) $item->payment_count,
                    'customer_count' => (int) $item->customer_count,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildMonthlyCollectionsPayload(int $ispId, int $months = 12, ?string $startDate = null, ?string $endDate = null): array
    {
        $months = max(1, min(36, $months));
        $start = Carbon::parse($startDate ?: now()->subMonths($months - 1)->startOfMonth()->toDateString())->startOfMonth();
        $end = Carbon::parse($endDate ?: now()->endOfMonth()->toDateString())->endOfMonth();

        $data = Payment::where('isp_id', $ispId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end])
            ->select(
                DB::raw('DATE_FORMAT(COALESCE(processed_at, created_at), "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('COUNT(DISTINCT customer_id) as customer_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels = [];
        $revenue = [];
        $paymentCounts = [];
        $customerCounts = [];

        $current = $start->copy();
        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $labels[] = $current->format('M Y');
            $item = $data->get($monthKey);
            $revenue[] = (float) ($item->total_revenue ?? 0);
            $paymentCounts[] = (int) ($item->payment_count ?? 0);
            $customerCounts[] = (int) ($item->customer_count ?? 0);
            $current->addMonth();
        }

        $peakIndex = $revenue ? array_search(max($revenue), $revenue, true) : false;

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Collections (KES)',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.35)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'summary' => [
                'total_revenue' => array_sum($revenue),
                'average_monthly' => count($revenue) > 0 ? array_sum($revenue) / count($revenue) : 0,
                'total_payments' => array_sum($paymentCounts),
                'total_customers' => array_sum($customerCounts),
                'peak_month' => $peakIndex === false ? null : $labels[$peakIndex],
                'peak_revenue' => $revenue ? max($revenue) : 0,
            ],
            'details' => array_map(function ($label, $rev, $payments, $customers) {
                return [
                    'month' => $label,
                    'revenue' => $rev,
                    'payment_count' => $payments,
                    'customer_count' => $customers,
                ];
            }, $labels, $revenue, $paymentCounts, $customerCounts),
        ];
    }

    public function customerStatusReport(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->wantsReportJson($request)) {
            return $this->redirectToReportFocus('customer-growth');
        }

        return response()->json(
            $this->buildCustomerGrowthPayload(
                (int) auth()->user()->isp_id,
                (int) $request->get('months', 12)
            )
        );
    }

    public function revenuePerPlanReport(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->wantsReportJson($request)) {
            return $this->redirectToReportFocus('revenue-per-plan');
        }

        return response()->json(
            $this->buildRevenuePerPlanPayload(
                (int) auth()->user()->isp_id,
                $request->get('start_date', now()->startOfYear()->toDateString()),
                $request->get('end_date', now()->toDateString())
            )
        );
    }

    public function monthlyCollectionsReport(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->wantsReportJson($request)) {
            return $this->redirectToReportFocus('monthly-collections');
        }

        return response()->json(
            $this->buildMonthlyCollectionsPayload(
                (int) auth()->user()->isp_id,
                (int) $request->get('months', 12),
                $request->get('start_date'),
                $request->get('end_date')
            )
        );
    }

    public function exportRevenueCsv(Request $request): StreamedResponse
    {
        $payload = $this->buildRevenuePerPlanPayload(
            (int) auth()->user()->isp_id,
            $request->get('start_date', now()->startOfYear()->toDateString()),
            $request->get('end_date', now()->toDateString())
        );

        return CsvExporter::download(
            'revenue-per-plan-'.now()->format('Ymd-His').'.csv',
            ['Plan', 'Revenue', 'Payments', 'Customers'],
            function ($out) use ($payload) {
                foreach ($payload['details'] as $row) {
                    fputcsv($out, [
                        $row['plan_name'],
                        $row['revenue'],
                        $row['payment_count'],
                        $row['customer_count'],
                    ]);
                }
            }
        );
    }

    public function exportCollectionsCsv(Request $request): StreamedResponse
    {
        $payload = $this->buildMonthlyCollectionsPayload(
            (int) auth()->user()->isp_id,
            (int) $request->get('months', 12),
            $request->get('start_date'),
            $request->get('end_date')
        );

        return CsvExporter::download(
            'monthly-collections-'.now()->format('Ymd-His').'.csv',
            ['Month', 'Revenue', 'Payments', 'Customers'],
            function ($out) use ($payload) {
                foreach ($payload['details'] as $row) {
                    fputcsv($out, [
                        $row['month'],
                        $row['revenue'],
                        $row['payment_count'],
                        $row['customer_count'],
                    ]);
                }
            }
        );
    }

    public function exportGrowthCsv(Request $request): StreamedResponse
    {
        $payload = $this->buildCustomerGrowthPayload(
            (int) auth()->user()->isp_id,
            (int) $request->get('months', 12)
        );

        return CsvExporter::download(
            'customer-growth-'.now()->format('Ymd-His').'.csv',
            ['Month', 'New customers'],
            function ($out) use ($payload) {
                foreach ($payload['growth']['details'] as $row) {
                    fputcsv($out, [
                        $row['month'],
                        $row['new_customers'],
                    ]);
                }
            }
        );
    }
}
