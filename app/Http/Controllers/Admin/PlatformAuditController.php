<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ISP;
use App\Models\RouterActionLog;
use App\Support\ActivityLogAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAuditController extends Controller
{
    public function activity(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $query = ActivityLog::query()->with(['user', 'isp'])->latest();

        if ($request->filled('isp_id')) {
            $query->where('isp_id', $request->integer('isp_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('severity')) {
            ActivityLogAudit::applySeverityFilter($query, (string) $request->severity);
        }

        if ($request->filled('module')) {
            ActivityLogAudit::applyModuleFilter($query, (string) $request->module);
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(40)->appends($request->query());
        $isps = ISP::query()->orderBy('name')->get(['id', 'name']);

        $snapshot = [
            'failed_logins_24h' => ActivityLog::query()
                ->where('action', 'login_failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'router_api_failures_24h' => ActivityLog::query()
                ->where('action', 'router_api_connection_failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'events_24h' => ActivityLog::query()
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];

        return view('admin.platform-audit.activity', [
            'logs' => $logs,
            'isps' => $isps,
            'snapshot' => $snapshot,
        ]);
    }

    public function showActivity(Request $request, ActivityLog $activityLog): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if (($activityLog->metadata['demo'] ?? false) === true) {
            abort(404);
        }

        $activityLog->load(['user', 'isp']);

        return view('admin.platform-audit.activity-show', compact('activityLog'));
    }

    public function security(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $failedLogins = ActivityLog::query()
            ->with(['user', 'isp'])
            ->where('action', 'login_failed')
            ->latest()
            ->paginate(25, ['*'], 'logins_page')
            ->appends($request->except('logins_page'));

        $routerApiFailures = ActivityLog::query()
            ->with(['user', 'isp'])
            ->where('action', 'router_api_connection_failed')
            ->latest()
            ->paginate(25, ['*'], 'api_page')
            ->appends($request->except('api_page'));

        $failedRouterJobs = RouterActionLog::query()
            ->with(['isp', 'router', 'user'])
            ->where('status', 'failed')
            ->latest()
            ->paginate(25, ['*'], 'jobs_page')
            ->appends($request->except('jobs_page'));

        return view('admin.platform-audit.security', compact('failedLogins', 'routerApiFailures', 'failedRouterJobs'));
    }
}
