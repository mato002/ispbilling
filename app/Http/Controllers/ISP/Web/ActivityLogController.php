<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\ISP\BaseController;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends BaseController
{
    public function index(Request $request)
    {
        $ispId = $this->getIspId();
        
        $query = ActivityLog::where('isp_id', $ispId)
            ->with(['user', 'isp'])
            ->latest();

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model type
        if ($request->has('model_type') && $request->model_type) {
            $query->where('model_type', $request->model_type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        return view('isp.logs.activity', compact('logs'));
    }

    public function show(ActivityLog $activityLog)
    {
        $this->ensureIspAccess($activityLog);

        $activityLog->load(['user', 'isp']);

        return view('isp.logs.activity-show', compact('activityLog'));
    }
}





