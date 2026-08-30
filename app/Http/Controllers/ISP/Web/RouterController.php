<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Jobs\BackupRouterConfigJob;
use App\Jobs\PollRouterHealthJob;
use App\Jobs\RebootRouterJob;
use App\Models\Customer;
use App\Models\Router;
use App\Models\RouterBackup;
use App\Models\RouterMetric;
use App\Models\User;
use App\Services\MikroTikService;
use App\Support\FormSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RouterController extends Controller
{
    use InteractsWithListings;

    public function __construct(private readonly FormSettingsService $formSettings) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $fleetBase = Router::where('isp_id', $ispId)->withCount('customers');
        $fleetStats = $this->buildFleetStats(clone $fleetBase);
        $networkInsights = $this->buildNetworkInsights(clone $fleetBase);

        $query = $this->filteredRoutersQuery($request, $ispId);

        $perPage = $this->resolvePerPage($request);
        $routers = $query->paginate($perPage)->withQueryString();

        $routers->through(function (Router $router) {
            $router->setAttribute('noc', $router->operationalSnapshot());

            return $router;
        });

        $filterChips = $this->listingFilterChips($request, route('isp.routers.index'), [
            'q' => 'Search',
            'is_active' => [
                'label' => 'Status',
                'values' => ['1' => 'Active', '0' => 'Inactive'],
            ],
            'branch' => 'Branch',
            'health' => [
                'label' => 'Reachability',
                'values' => [
                    'online' => 'Online',
                    'offline' => 'Offline',
                    'warn' => 'Degraded',
                    'ok' => 'Healthy',
                    'unknown' => 'Unknown',
                ],
            ],
            'conn_type' => [
                'label' => 'Transport',
                'values' => ['ssl' => 'SSL', 'plain' => 'Plain'],
            ],
            'lifecycle' => 'Lifecycle',
            'sort' => [
                'label' => 'Sort',
                'values' => [
                    'latest' => 'Newest',
                    'name' => 'Name',
                    'latency' => 'Latency',
                    'health' => 'Health',
                    'customers' => 'Customers',
                ],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.routers.export.csv'));

        return view('isp.routers.index', compact(
            'routers',
            'fleetStats',
            'networkInsights',
            'filterChips',
            'exportFormats',
        ));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Router>
     */
    protected function filteredRoutersQuery(Request $request, int $ispId)
    {
        $query = Router::where('isp_id', $ispId)->withCount('customers');

        if ($request->filled('q')) {
            $q = '%'.$request->q.'%';
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', $q)
                    ->orWhere('ip_address', 'like', $q)
                    ->orWhere('location', 'like', $q)
                    ->orWhere('branch', 'like', $q)
                    ->orWhere('serial_number', 'like', $q)
                    ->orWhere('tags', 'like', $q);
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('branch')) {
            $query->where(function ($w) use ($request) {
                $w->where('branch', 'like', '%'.$request->branch.'%')
                    ->orWhere('location', 'like', '%'.$request->branch.'%');
            });
        }

        if ($request->filled('conn_type')) {
            if ($request->conn_type === 'ssl') {
                $query->where('use_ssl', true);
            } elseif ($request->conn_type === 'plain') {
                $query->where('use_ssl', false);
            }
        }

        if ($request->filled('health')) {
            match ($request->health) {
                'offline' => $query->where(function ($w) {
                    $w->where('is_active', false)->orWhere('reachability_status', 'offline');
                }),
                'online' => $query->where('reachability_status', 'online')->where('is_active', true),
                'warn' => $query->whereIn('reachability_status', ['degraded', 'rebooting']),
                'ok' => $query->where('reachability_status', 'online')->where('last_latency_ms', '<=', 35),
                'unknown' => $query->where(function ($w) {
                    $w->where('reachability_status', 'unknown')->orWhereNull('last_telemetry_at');
                }),
                default => null,
            };
        }

        if ($request->filled('lifecycle')) {
            $query->where('lifecycle_status', $request->lifecycle);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'latency' => $query->orderByRaw('last_latency_ms IS NULL, last_latency_ms ASC'),
            'health' => $query->orderByDesc('last_health_pct'),
            'customers' => $query->orderByDesc('customers_count'),
            default => $query->latest(),
        };

        return $query;
    }

    /**
     * Fleet-wide counters for the NOC header. Now backed by cached telemetry.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Router>  $base
     */
    private function buildFleetStats($base): array
    {
        $total = (clone $base)->count();
        $online = (clone $base)->where('reachability_status', 'online')->where('is_active', true)->count();
        $offline = (clone $base)->where(function ($w) {
            $w->where('is_active', false)->orWhere('reachability_status', 'offline');
        })->count();
        $degraded = (clone $base)->whereIn('reachability_status', ['degraded', 'rebooting'])->count();

        $connectedCustomers = (int) Customer::where('isp_id', auth()->user()->isp_id)
            ->whereHas('router')
            ->count();

        $latencyAgg = (clone $base)
            ->whereNotNull('last_latency_ms')
            ->avg('last_latency_ms');
        $avgLatency = $latencyAgg !== null ? (int) round($latencyAgg) : null;

        $healthAgg = (clone $base)
            ->whereNotNull('last_health_pct')
            ->avg('last_health_pct');
        $networkHealth = $healthAgg !== null ? (int) round($healthAgg) : null;

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'degraded' => $degraded,
            'connected_customers' => $connectedCustomers,
            'avg_latency_ms' => $avgLatency,
            'network_health' => $networkHealth,
            'with_telemetry' => (clone $base)->whereNotNull('last_telemetry_at')->count(),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Router>  $base
     */
    private function buildNetworkInsights($base): array
    {
        $withCustomers = (clone $base)->withCount('customers')->get();

        if ($withCustomers->isEmpty()) {
            return [
                'busiest' => null,
                'most_subscribers' => null,
                'last_offline' => null,
                'congestion_note' => 'No routers in fleet',
            ];
        }

        $busiest = $withCustomers->sortByDesc('customers_count')->first();
        $mostSubs = $busiest;
        $offlineRouters = $withCustomers
            ->where('reachability_status', 'offline')
            ->sortByDesc('last_telemetry_at');
        $lastOffline = $offlineRouters->first();

        $note = match (true) {
            $withCustomers->whereNotNull('last_telemetry_at')->count() === 0 => 'No telemetry yet — wait for the next minute’s poll cycle',
            $offlineRouters->isNotEmpty() => $offlineRouters->count().' router(s) currently offline',
            $withCustomers->where('reachability_status', 'degraded')->isNotEmpty() => 'Some routers reporting elevated latency',
            default => 'Fleet healthy — no congestion signals',
        };

        return [
            'busiest' => $busiest,
            'most_subscribers' => $mostSubs,
            'last_offline' => $lastOffline,
            'congestion_note' => $note,
        ];
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $owners = User::where('isp_id', $ispId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $formFields = $this->formSettings->resolvedFields('router', $ispId);
        $routerDefaults = data_get(auth()->user()->isp?->settings, 'network.router_defaults', []);

        return view('isp.routers.create', compact('owners', 'formFields', 'routerDefaults'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRouterPayload($request);

        $defaults = data_get(auth()->user()->isp?->settings, 'network.router_defaults', []);
        $data['isp_id'] = auth()->user()->isp_id;
        $data['use_ssl'] = $request->has('use_ssl')
            ? $request->boolean('use_ssl')
            : (bool) ($defaults['use_ssl_by_default'] ?? false);
        $data['is_active'] = $request->boolean('is_active', true);

        if (empty($data['port']) && ! empty($defaults['default_api_port'])) {
            $data['port'] = (int) $defaults['default_api_port'];
        }
        if (empty($data['lifecycle_status']) && ! empty($defaults['lifecycle_default'])) {
            $data['lifecycle_status'] = $defaults['lifecycle_default'];
        }

        $router = Router::create($data);

        try {
            $mikrotik = new MikroTikService($router);
            $result = $mikrotik->testConnection();

            if ($result['success']) {
                $importedCount = $this->importHotspotServers($router, $result['hotspot_servers']);
                // Kick the first poll immediately so the row shows live numbers.
                PollRouterHealthJob::dispatch($router->id);

                $msg = $importedCount > 0
                    ? "Router added! Connection OK, {$importedCount} hotspot servers imported, first telemetry queued."
                    : 'Router added! Connection OK, first telemetry queued.';

                return redirect()->route('isp.routers.index')->with('success', $msg);
            }

            return redirect()->route('isp.routers.index')
                ->with('warning', 'Router added but connection test failed: '.$result['error'].'. Test connection manually after fixing credentials/firewall.');
        } catch (\Exception $e) {
            return redirect()->route('isp.routers.index')
                ->with('warning', 'Router added but automatic connection test failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $hotspotServers
     */
    private function importHotspotServers(Router $router, array $hotspotServers): int
    {
        $importedCount = 0;
        foreach ($hotspotServers as $serverData) {
            try {
                \App\Models\HotspotServer::updateOrCreate(
                    ['router_id' => $router->id, 'name' => $serverData['name']],
                    [
                        'isp_id' => $router->isp_id,
                        'interface' => $serverData['interface'] ?? 'unknown',
                        'server_profile' => $serverData['server_profile'] ?? null,
                        'default_profile' => $serverData['default_profile'] ?? null,
                        'login_by' => $serverData['login_by'] ?? 'cookie',
                        'use_radius' => $serverData['use_radius'] ?? false,
                        'radius_accounting' => $serverData['radius_accounting'] ?? false,
                        'mac_cookie_timeout' => $serverData['mac_cookie_timeout'] ?? 300,
                        'idle_timeout' => $serverData['idle_timeout'] ?? 300,
                        'keepalive_timeout' => $serverData['keepalive_timeout'] ?? 60,
                        'status_autologout' => $serverData['status_autologout'] ?? false,
                        'allowed_users' => $serverData['allowed_users'] ?? 0,
                        'shared_users' => $serverData['shared_users'] ?? 0,
                        'mac_format' => $serverData['mac_format'] ?? null,
                        'mac_password' => $serverData['mac_password'] ?? null,
                        'radius_mac_format' => $serverData['radius_mac_format'] ?? null,
                        'radius_mac_password' => $serverData['radius_mac_password'] ?? null,
                        'trial_uptime' => $serverData['trial_uptime'] ?? 0,
                        'trial_uptime_reset' => $serverData['trial_uptime_reset'] ?? 0,
                        'trial_profile' => $serverData['trial_profile'] ?? null,
                        'trial_users' => $serverData['trial_users'] ?? 0,
                        'disabled' => $serverData['disabled'] ?? false,
                        'running' => $serverData['running'] ?? false,
                        'is_active' => ! ($serverData['disabled'] ?? false),
                    ]
                );
                $importedCount++;
            } catch (\Exception $e) {
                \Log::error('Failed to import hotspot server: '.$e->getMessage(), [
                    'router_id' => $router->id,
                    'server_name' => $serverData['name'] ?? 'unknown',
                ]);
            }
        }

        return $importedCount;
    }

    public function show(Router $router)
    {
        $this->authorizeRouter($router);
        $router->loadCount('customers');
        $router->load(['owner']);

        $metrics = RouterMetric::where('router_id', $router->id)
            ->orderByDesc('recorded_at')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        $latencySeries = $metrics->pluck('latency_ms')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->values()->all();
        $healthSeries = $metrics->pluck('health_pct')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->values()->all();
        $cpuSeries = $metrics->pluck('cpu_pct')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->values()->all();
        $memSeries = $metrics->pluck('mem_pct')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->values()->all();

        $actionLogs = \App\Models\RouterActionLog::where('router_id', $router->id)
            ->latest()
            ->limit(30)
            ->get();

        $backups = RouterBackup::where('router_id', $router->id)
            ->latest()
            ->limit(10)
            ->get();

        $snapshot = $router->operationalSnapshot();

        return view('isp.routers.show', compact(
            'router', 'snapshot', 'metrics',
            'latencySeries', 'healthSeries', 'cpuSeries', 'memSeries',
            'actionLogs', 'backups'
        ));
    }

    public function edit(Router $router)
    {
        $this->authorizeRouter($router);
        $owners = User::where('isp_id', auth()->user()->isp_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $formFields = $this->formSettings->resolvedFields('router', $router->isp_id);

        return view('isp.routers.edit', compact('router', 'owners', 'formFields'));
    }

    public function update(Request $request, Router $router)
    {
        $this->authorizeRouter($router);

        $data = $this->validateRouterPayload($request, $router);
        $data['use_ssl'] = $request->boolean('use_ssl');
        $data['is_active'] = $request->boolean('is_active');

        // Do not overwrite the credential when the field is empty in the form.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $router->update($data);

        return redirect()->route('isp.routers.show', $router)->with('success', 'Router updated successfully');
    }

    public function activate(Router $router)
    {
        $this->authorizeRouter($router);
        $router->update(['is_active' => true]);
        PollRouterHealthJob::dispatch($router->id);

        return back()->with('success', 'Router activated. First telemetry poll queued.');
    }

    public function deactivate(Router $router)
    {
        $this->authorizeRouter($router);
        $router->update([
            'is_active' => false,
            'reachability_status' => 'offline',
        ]);

        return back()->with('success', 'Router deactivated.');
    }

    /**
     * Connection test from the create-router form (no router saved yet).
     */
    public function testConnection(Request $request)
    {
        $this->enforceTestRateLimit($request);

        $request->validate([
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'use_ssl' => 'nullable|boolean',
        ]);

        $tempRouter = new Router([
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'username' => $request->username,
            'password' => $request->password,
            'use_ssl' => $request->boolean('use_ssl'),
        ]);

        try {
            $mikrotik = new MikroTikService($tempRouter);
            $result = $mikrotik->testConnection();

            return response()->json($result['success'] ? [
                'success' => true,
                'message' => 'Connection successful! Router is online.',
                'router_info' => $result['router_info'],
                'hotspot_servers' => $result['hotspot_servers'],
                'hotspot_servers_count' => $result['hotspot_servers_count'],
            ] : [
                'success' => false,
                'error' => $result['error'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed: '.$e->getMessage(),
            ]);
        }
    }

    public function testExistingConnection(Router $router)
    {
        $this->authorizeRouter($router);
        $this->enforceTestRateLimit(request());

        try {
            $mikrotik = new MikroTikService($router);
            $result = $mikrotik->testConnection();
            if ($result['success']) {
                PollRouterHealthJob::dispatch($router->id);

                return back()->with('success', 'Connection OK. Found '.$result['hotspot_servers_count'].' hotspot servers. Telemetry queued.');
            }

            return back()->with('error', 'Failed to connect: '.$result['error']);
        } catch (\Exception $e) {
            return back()->with('error', 'Connection test failed: '.$e->getMessage());
        }
    }

    /**
     * Force an immediate telemetry poll for this router.
     */
    public function poll(Router $router)
    {
        $this->authorizeRouter($router);
        PollRouterHealthJob::dispatch($router->id);

        return back()->with('success', "Telemetry poll queued for {$router->name}.");
    }

    /**
     * Issue a remote reboot via queued job.
     */
    public function reboot(Router $router)
    {
        $this->authorizeRouter($router);
        RebootRouterJob::dispatch($router->id, auth()->id());

        return back()->with('success', "Reboot command queued for {$router->name}. The device should be back in ~60-90 seconds.");
    }

    /**
     * Run an on-demand ping from the router and persist the result to the action log.
     */
    public function ping(Request $request, Router $router)
    {
        $this->authorizeRouter($router);
        $data = $request->validate([
            'address' => 'required|string|max:255',
            'count' => 'nullable|integer|min:1|max:20',
        ]);

        $service = new MikroTikService($router);
        $result = $service->ping($data['address'], (int) ($data['count'] ?? 4));

        \App\Models\RouterActionLog::log([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'user_id' => auth()->id(),
            'action' => 'ping_from_router',
            'status' => $result['received'] > 0 ? 'success' : 'failed',
            'description' => sprintf(
                'Ping %s — %d/%d received, avg %s ms',
                $data['address'],
                $result['received'],
                $result['sent'],
                $result['avg_ms'] !== null ? (string) $result['avg_ms'] : '—'
            ),
            'request_data' => $data,
            'response_data' => ['raw' => $result['raw'], 'avg_ms' => $result['avg_ms']],
        ]);

        return back()->with(
            $result['received'] > 0 ? 'success' : 'warning',
            'Ping '.$data['address'].': '.$result['received'].'/'.$result['sent'].
            ' received'.($result['avg_ms'] !== null ? ', avg '.$result['avg_ms'].' ms' : '')
        );
    }

    /**
     * Queue a config backup (/export) job.
     */
    public function backup(Router $router)
    {
        $this->authorizeRouter($router);
        BackupRouterConfigJob::dispatch($router->id, auth()->id());

        return back()->with('success', 'Config backup queued. It will appear in the Backups tab shortly.');
    }

    /**
     * Download a stored backup.
     */
    public function downloadBackup(Router $router, RouterBackup $backup)
    {
        $this->authorizeRouter($router);
        if ($backup->router_id !== $router->id || $backup->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $disk = Storage::disk($backup->storage_disk);
        if (! $disk->exists($backup->storage_path)) {
            abort(404, 'Backup file missing on storage');
        }

        return $disk->download($backup->storage_path, $backup->filename);
    }

    /**
     * Bulk action across selected routers.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:poll,test,backup,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)
            ->whereIn('id', $data['ids'])
            ->get();

        $count = 0;
        foreach ($routers as $router) {
            switch ($data['action']) {
                case 'poll':
                    PollRouterHealthJob::dispatch($router->id);
                    $count++;
                    break;
                case 'test':
                    // Mark for re-test via a queued poll, which exercises the same code path.
                    PollRouterHealthJob::dispatch($router->id);
                    $count++;
                    break;
                case 'backup':
                    BackupRouterConfigJob::dispatch($router->id, auth()->id());
                    $count++;
                    break;
                case 'activate':
                    $router->update(['is_active' => true]);
                    PollRouterHealthJob::dispatch($router->id);
                    $count++;
                    break;
                case 'deactivate':
                    $router->update(['is_active' => false, 'reachability_status' => 'offline']);
                    $count++;
                    break;
            }
        }

        return back()->with('success', "Bulk action '{$data['action']}' queued for {$count} router(s).");
    }

    /**
     * CSV export of the filtered fleet.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredRoutersQuery($request, $ispId);

        $filename = 'routers-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID', 'Name', 'IP', 'Port', 'SSL', 'Active', 'Reachability',
                'Branch', 'Vendor', 'Model', 'Serial', 'MAC',
                'Customers', 'Latency (ms)', 'Health %', 'CPU %', 'RAM %',
                'Firmware', 'Last seen', 'Tags', 'Monthly cost',
            ]);
            $query->orderBy('name')->chunk(200, function ($routers) use ($out) {
                foreach ($routers as $router) {
                    fputcsv($out, [
                        $router->id,
                        $router->name,
                        $router->ip_address,
                        $router->port,
                        $router->use_ssl ? 'yes' : 'no',
                        $router->is_active ? 'yes' : 'no',
                        $router->reachability_status,
                        $router->branch,
                        $router->vendor,
                        $router->model,
                        $router->serial_number,
                        $router->mac_address,
                        $router->customers_count,
                        $router->last_latency_ms,
                        $router->last_health_pct,
                        $router->last_cpu_pct,
                        $router->last_mem_pct,
                        $router->last_firmware,
                        optional($router->last_seen_at)->format('Y-m-d H:i:s'),
                        $router->tags,
                        $router->monthly_cost,
                    ]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Authorisation guard used by every action that takes a {router}.
     */
    protected function authorizeRouter(Router $router): void
    {
        if ($router->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
    }

    /**
     * Per-ISP rate limit for the test-connection endpoints. Stops a tenant
     * (or a stolen session) from hammering arbitrary IPs through our app.
     */
    protected function enforceTestRateLimit(Request $request): void
    {
        $key = 'router-test:'.auth()->user()->isp_id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Too many connection tests. Please wait a minute.');
        }
        RateLimiter::hit($key, 60);
    }

    /**
     * Validation for create + update. Password is optional on update so
     * operators can edit asset metadata without re-typing the credential.
     *
     * @return array<string, mixed>
     */
    protected function validateRouterPayload(Request $request, ?Router $existing = null): array
    {
        $ispId = auth()->user()->isp_id;

        $rules = [
            'name' => ['string', 'max:255'],
            'ip_address' => ['ip'],
            'port' => ['integer', 'min:1', 'max:65535'],
            'username' => ['string', 'max:255'],
            'password' => ['string', 'max:255'],
            'location' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'use_ssl' => ['boolean'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'branch' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:512'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'lifecycle_status' => ['nullable', 'in:stock,live,maintenance,retired'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date'],
            'monthly_cost' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($existing && ! $request->filled('password')) {
            unset($rules['password']);
            $request->request->remove('password');
        }

        return $this->formSettings->validateRequest($request, 'router', $rules, $ispId);
    }
}
