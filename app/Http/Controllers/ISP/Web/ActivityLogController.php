<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\ActivityLog;
use App\Models\Router;
use App\Models\User;
use App\Support\ActivityLogAudit;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends BaseController
{
    public function index(Request $request)
    {
        $ispId = $this->getIspId();

        $baseQuery = ActivityLog::where('isp_id', $ispId)->with(['user', 'isp']);

        $statsQuery = ActivityLog::where('isp_id', $ispId);
        $todayStart = Carbon::today();

        $auditStats = [
            'events_today' => (clone $statsQuery)->whereDate('created_at', $todayStart)->count(),
            'failed_logins' => (clone $statsQuery)->whereDate('created_at', $todayStart)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(action) LIKE ?', ['%fail%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%failed login%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%invalid credentials%']);
                })->count(),
            'customer_actions' => (clone $statsQuery)->whereDate('created_at', $todayStart)
                ->where('model_type', 'like', '%Customer%')->count(),
            'billing_changes' => (clone $statsQuery)->whereDate('created_at', $todayStart)
                ->where(function ($q) {
                    $q->where('model_type', 'like', '%Payment%')
                        ->orWhere('model_type', 'like', '%Invoice%')
                        ->orWhere('model_type', 'like', '%Subscription%')
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%payment%'])
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%invoice%']);
                })->count(),
            'router_changes' => (clone $statsQuery)->whereDate('created_at', $todayStart)
                ->where(function ($q) {
                    $q->where('model_type', 'like', '%Router%')
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%router%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%mikrotik%']);
                })->count(),
        ];

        $securityAlertsQuery = ActivityLog::where('isp_id', $ispId)->whereDate('created_at', $todayStart)
            ->where(function ($q) {
                $q->where(function ($w) {
                    ActivityLogAudit::applySeverityFilter($w, 'critical');
                })->orWhere(function ($w) {
                    ActivityLogAudit::applySeverityFilter($w, 'danger');
                });
            });
        $auditStats['security_alerts'] = $securityAlertsQuery->count();

        $systemStablePct = $auditStats['events_today'] > 0
            ? min(99.99, 99.0 + (100 - min(100, $auditStats['security_alerts'] * 5)) * 0.01)
            : 100.0;

        $sidebarMetrics = [
            'active_staff' => User::where('isp_id', $ispId)->where('is_active', true)->count(),
            'last_login_at' => ActivityLog::where('isp_id', $ispId)
                ->where('user_id', auth()->id())
                ->where('action', 'login')
                ->latest()
                ->value('created_at'),
            'failed_auth_today' => $auditStats['failed_logins'],
            'routers_active' => Router::where('isp_id', $ispId)->where('is_active', true)->count(),
            'system_changes_today' => (clone $statsQuery)->whereDate('created_at', $todayStart)
                ->where(function ($q) {
                    $q->whereNull('model_type')
                        ->orWhere('model_type', 'like', '%User%');
                })->count(),
        ];

        $staffUsers = User::where('isp_id', $ispId)->orderBy('name')->get(['id', 'name', 'email']);

        $query = (clone $baseQuery)->latest();
        $this->applyActivityListFilters($query, $request);

        $logs = $query->paginate(30)->appends($request->query());

        $isDemoStream = false;
        $anyFilter = collect(['search', 'action', 'user_id', 'model_type', 'severity', 'module', 'event_type', 'date_from', 'date_to'])
            ->contains(fn (string $k) => $request->filled($k));
        // Demo filler only when explicitly requested in local/dev — never masquerade as real audits.
        if (
            $logs->isEmpty()
            && ! $anyFilter
            && app()->environment(['local', 'development'])
            && $request->boolean('demo')
        ) {
            $logs = $this->demoPaginator($ispId, $request);
            $isDemoStream = true;
        }

        $tickerSource = $logs->getCollection();
        if ($tickerSource->isEmpty() && ! $isDemoStream) {
            $tickerSource = (clone $baseQuery)->latest()->limit(8)->get();
        }
        $tickerEvents = $tickerSource->take(8);

        $distinctModelTypes = ActivityLog::where('isp_id', $ispId)
            ->whereNotNull('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->limit(40)
            ->pluck('model_type');

        return view('isp.logs.activity', compact(
            'logs',
            'auditStats',
            'sidebarMetrics',
            'staffUsers',
            'tickerEvents',
            'distinctModelTypes',
            'isDemoStream',
            'systemStablePct'
        ));
    }

    public function exportCsv(Request $request)
    {
        $query = $this->filteredActivityLogsQuery($request)->latest()->limit(2000);
        $logs = $query->get();

        $filename = 'activity-audit-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id', 'created_at', 'severity', 'module', 'user', 'email', 'action', 'description', 'ip_address', 'model_type', 'model_id',
            ]);
            foreach ($logs as $log) {
                $moduleKey = ActivityLogAudit::moduleKey($log);
                $moduleLabel = ActivityLogAudit::MODULES[$moduleKey] ?? $moduleKey;
                fputcsv($out, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    ActivityLogAudit::severity($log),
                    $moduleLabel,
                    $log->user?->name,
                    $log->user?->email,
                    $log->action,
                    $log->description,
                    $log->ip_address,
                    $log->model_type,
                    $log->model_id,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        if (! class_exists(Dompdf::class)) {
            Log::warning('Dompdf not available for activity export PDF.');

            return redirect()
                ->route('isp.logs.activity.index', $request->except('_token'))
                ->with('error', 'PDF export is not available on this server.');
        }

        $logs = $this->filteredActivityLogsQuery($request)->latest()->limit(150)->get();

        $html = view('isp.logs.activity-export-pdf', [
            'logs' => $logs,
            'exportedAt' => now(),
            'ispName' => auth()->user()?->isp?->name,
        ])->render();

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->stream('activity-audit-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function show(ActivityLog $activityLog)
    {
        $this->ensureIspAccess($activityLog);

        if (($activityLog->metadata['demo'] ?? false) === true) {
            abort(404);
        }

        $activityLog->load(['user', 'isp']);

        return view('isp.logs.activity-show', compact('activityLog'));
    }

    protected function filteredActivityLogsQuery(Request $request): Builder
    {
        $ispId = $this->getIspId();
        $query = ActivityLog::where('isp_id', $ispId)->with(['user']);
        $this->applyActivityListFilters($query, $request);

        return $query;
    }

    protected function applyActivityListFilters(Builder $query, Request $request): void
    {
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }
        if ($request->filled('severity')) {
            ActivityLogAudit::applySeverityFilter($query, (string) $request->severity);
        }
        if ($request->filled('module')) {
            ActivityLogAudit::applyModuleFilter($query, (string) $request->module);
        }
        if ($request->filled('event_type')) {
            ActivityLogAudit::applyEventTypeFilter($query, (string) $request->event_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function demoPaginator(?int $ispId, Request $request): LengthAwarePaginator
    {
        $now = Carbon::now();
        $rows = [
            ['action' => 'login', 'description' => 'ISP Admin logged in successfully', 'model_type' => null, 'ip' => '102.208.112.44', 'mins' => 2, 'user' => 'Nia Mwangi'],
            ['action' => 'invoice_generated', 'description' => 'Invoice #1024 generated for customer ACC-4412', 'model_type' => 'App\Models\Invoice', 'ip' => '102.208.112.44', 'mins' => 5, 'user' => 'System'],
            ['action' => 'customer_suspended', 'description' => 'Customer ACC-2201 suspended — overdue balance', 'model_type' => 'App\Models\Customer', 'ip' => '102.208.112.44', 'mins' => 8, 'user' => 'Nia Mwangi'],
            ['action' => 'router_offline', 'description' => 'MikroTik router Branch-01 unreachable (timeout)', 'model_type' => 'App\Models\Router', 'ip' => '10.0.0.1', 'mins' => 12, 'user' => 'Monitor'],
            ['action' => 'hotspot_activated', 'description' => 'Hotspot profile Night-Express activated on Main Tower', 'model_type' => 'App\Models\HotspotServer', 'ip' => '41.90.12.8', 'mins' => 18, 'user' => 'K. Otieno'],
            ['action' => 'pppoe_disconnect', 'description' => 'PPPoE session dropped for user cust-pppoe-992', 'model_type' => null, 'ip' => '—', 'mins' => 21, 'user' => 'Radius'],
            ['action' => 'payment_recorded', 'description' => 'KES 3,500 payment recorded against Invoice #1024', 'model_type' => 'App\Models\Payment', 'ip' => '102.208.112.44', 'mins' => 26, 'user' => 'Nia Mwangi'],
            ['action' => 'plan_updated', 'description' => 'Bandwidth plan Fiber-50 updated (upload cap adjusted)', 'model_type' => 'App\Models\Plan', 'ip' => '102.208.112.44', 'mins' => 34, 'user' => 'K. Otieno'],
            ['action' => 'login_failed', 'description' => 'Failed login attempt — invalid credentials', 'model_type' => null, 'ip' => '197.232.44.11', 'mins' => 41, 'user' => '—'],
            ['action' => 'voucher_redeemed', 'description' => 'Voucher PB-8H-9921 redeemed at Hotspot Main', 'model_type' => 'App\Models\Voucher', 'ip' => '41.90.12.8', 'mins' => 48, 'user' => 'System'],
            ['action' => 'system_backup', 'description' => 'Automated configuration snapshot completed', 'model_type' => null, 'ip' => '127.0.0.1', 'mins' => 55, 'user' => 'System'],
            ['action' => 'ip_pool_warning', 'description' => 'DHCP pool Tower-A at 92% utilization — warning', 'model_type' => 'App\Models\IpPool', 'ip' => '10.8.0.1', 'mins' => 62, 'user' => 'Monitor'],
        ];

        $items = collect($rows)->map(function (array $row, int $i) use ($ispId, $now) {
            $u = User::make(['name' => $row['user'], 'email' => strtolower(str_replace([' ', '.'], ['', '@'], $row['user'])).'@demo.local']);

            return ActivityLog::make([
                'id' => -($i + 1),
                'isp_id' => $ispId,
                'user_id' => null,
                'action' => $row['action'],
                'model_type' => $row['model_type'],
                'model_id' => 1000 + $i,
                'description' => $row['description'],
                'ip_address' => $row['ip'],
                'user_agent' => 'DemoAuditStream/1.0',
                'old_values' => null,
                'new_values' => null,
                'metadata' => ['demo' => true],
                'created_at' => $now->copy()->subMinutes($row['mins']),
                'updated_at' => $now->copy()->subMinutes($row['mins']),
            ])->setRelation('user', $row['user'] === '—' || $row['user'] === 'System' || $row['user'] === 'Monitor' || $row['user'] === 'Radius' ? null : $u);
        })->values();

        $perPage = 30;
        $page = max(1, (int) $request->get('page', 1));

        return new LengthAwarePaginator(
            $items,
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
