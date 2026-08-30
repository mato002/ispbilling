<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemHealthController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $query = $this->filteredRoutersQuery($request);
        $perPage = $this->resolvePerPage($request, 40);
        $routers = $query->paginate($perPage)->withQueryString();

        $summary = Router::query()
            ->selectRaw('reachability_status, COUNT(*) as c')
            ->groupBy('reachability_status')
            ->pluck('c', 'reachability_status');

        $statusValues = $summary->keys()
            ->mapWithKeys(fn ($key) => [(string) $key => ($key !== '' ? (string) $key : 'unknown')])
            ->all();

        $statusFilter = $request->string('status')->toString();

        $filterChips = $this->listingFilterChips($request, route('admin.system-health.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Reachability',
                'values' => $statusValues,
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.system-health.export.csv'));

        return view('admin.system-health.index', [
            'routers' => $routers,
            'summary' => $summary,
            'statusFilter' => $statusFilter,
            'filterChips' => $filterChips,
            'exportFormats' => $exportFormats,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $query = $this->filteredRoutersQuery($request);

        return CsvExporter::download(
            'system-health-routers-'.now()->format('Ymd-His').'.csv',
            ['ID', 'ISP', 'Router', 'IP Address', 'Active', 'Reachability', 'Last Seen', 'Latency (ms)', 'Poll Error'],
            function ($out) use ($query) {
                $query->chunk(200, function ($routers) use ($out) {
                    foreach ($routers as $router) {
                        fputcsv($out, [
                            $router->id,
                            $router->isp?->name,
                            $router->name,
                            $router->ip_address,
                            $router->is_active ? 'Yes' : 'No',
                            $router->reachability_status,
                            $router->last_seen_at?->format('Y-m-d H:i:s'),
                            $router->last_latency_ms,
                            $router->last_poll_error,
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredRoutersQuery(Request $request)
    {
        $query = Router::query()
            ->with(['isp:id,name'])
            ->orderByDesc('reachability_status')
            ->orderBy('isp_id')
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->where('reachability_status', $request->input('status'));
        }

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('name', 'like', $pattern)
                    ->orWhere('ip_address', 'like', $pattern)
                    ->orWhereHas('isp', function ($ispQuery) use ($pattern) {
                        $ispQuery->where('name', 'like', $pattern);
                    });
            });
        }

        return $query;
    }
}
