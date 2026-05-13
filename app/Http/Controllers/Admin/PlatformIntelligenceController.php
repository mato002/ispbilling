<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformIntelligenceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $inactiveDays = max(1, min(90, (int) $request->get('inactive_days', 7)));
        $expireWithin = max(1, min(90, (int) $request->get('expire_within', 14)));

        $lastSessionSub = DB::table('hotspot_sessions')
            ->select('customer_id', DB::raw('MAX(COALESCE(ended_at, started_at)) as last_at'))
            ->whereNotNull('customer_id')
            ->groupBy('customer_id');

        $churnCandidates = Customer::query()
            ->select('customers.*')
            ->selectRaw('hs.last_at as churn_last_session_at')
            ->leftJoinSub($lastSessionSub, 'hs', 'hs.customer_id', '=', 'customers.id')
            ->where('customers.status', 'active')
            ->where(function ($q) use ($inactiveDays) {
                $q->whereNull('hs.last_at')
                    ->orWhere('hs.last_at', '<', now()->subDays($inactiveDays));
            })
            ->with(['isp:id,name', 'subscriptions' => fn ($q) => $q->where('status', 'active')->latest('end_date')->limit(1)])
            ->orderByRaw('(hs.last_at IS NULL) DESC')
            ->orderBy('hs.last_at')
            ->limit(200)
            ->get();

        $expiringSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays($expireWithin)->endOfDay()])
            ->with(['customer.isp', 'plan'])
            ->orderBy('end_date')
            ->limit(200)
            ->get();

        $forecast = $this->revenueForecast();

        return view('admin.platform-intelligence.index', [
            'inactiveDays' => $inactiveDays,
            'expireWithin' => $expireWithin,
            'churnCandidates' => $churnCandidates,
            'expiringSubscriptions' => $expiringSubscriptions,
            'forecast' => $forecast,
        ]);
    }

    /**
     * @return array{daily_avg: float, d30: float, d60: float, d90: float, sample_days: int}
     */
    protected function revenueForecast(): array
    {
        $windowDays = 90;
        $since = now()->subDays($windowDays)->startOfDay();
        $sum = (float) Payment::query()
            ->where('status', 'completed')
            ->whereRaw('COALESCE(processed_at, created_at) >= ?', [$since])
            ->sum('amount');

        $dailyAvg = $sum > 0 ? $sum / $windowDays : 0.0;

        return [
            'daily_avg' => round($dailyAvg, 2),
            'd30' => round($dailyAvg * 30, 2),
            'd60' => round($dailyAvg * 60, 2),
            'd90' => round($dailyAvg * 90, 2),
            'sample_days' => $windowDays,
        ];
    }
}
