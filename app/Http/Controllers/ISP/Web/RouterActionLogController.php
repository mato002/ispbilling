<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\RouterActionLog;
use Illuminate\Http\Request;

class RouterActionLogController extends BaseController
{
    public function index(Request $request)
    {
        $ispId = $this->getIspId();

        $query = RouterActionLog::where('isp_id', $ispId)
            ->with(['router', 'customer', 'user'])
            ->latest();

        // Filter by router
        if ($request->has('router_id') && $request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->appends($request->query());

        // Get routers for filter dropdown
        $routers = \App\Models\Router::where('isp_id', $ispId)->get();

        return view('isp.logs.router-actions', compact('logs', 'routers'));
    }

    public function show(RouterActionLog $routerActionLog)
    {
        $this->ensureIspAccess($routerActionLog);

        $routerActionLog->load(['router', 'customer', 'user', 'isp']);

        return view('isp.logs.router-action-show', compact('routerActionLog'));
    }
}
