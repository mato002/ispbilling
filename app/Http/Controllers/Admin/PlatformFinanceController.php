<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\IspPayout;
use App\Models\Payment;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformFinanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $commissionPercent = PlatformSetting::getDecimal('platform_commission_percent', 0.0);

        $months = min(24, max(3, (int) $request->get('months', 12)));
        $periodStart = now()->subMonths($months - 1)->startOfMonth();

        $monthlyFlow = Payment::query()
            ->where('payments.status', 'completed')
            ->whereRaw('COALESCE(payments.processed_at, payments.created_at) >= ?', [$periodStart])
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(payments.processed_at, payments.created_at), '%Y-%m') as ym"),
                DB::raw('SUM(payments.amount) as revenue'),
                DB::raw('SUM(COALESCE(payments.platform_fee_amount, 0)) as commission')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $revenueByIsp = Payment::query()
            ->where('payments.status', 'completed')
            ->whereRaw('COALESCE(payments.processed_at, payments.created_at) >= ?', [now()->subMonths(11)->startOfMonth()])
            ->join('i_s_p_s', 'payments.isp_id', '=', 'i_s_p_s.id')
            ->select('i_s_p_s.id', 'i_s_p_s.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('i_s_p_s.id', 'i_s_p_s.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $maxIspBar = (float) ($revenueByIsp->max('total') ?: 1);

        $commissionByIsp = Payment::query()
            ->where('payments.status', 'completed')
            ->whereNotNull('payments.platform_fee_amount')
            ->where('payments.platform_fee_amount', '>', 0)
            ->join('i_s_p_s', 'payments.isp_id', '=', 'i_s_p_s.id')
            ->select(
                'i_s_p_s.id',
                'i_s_p_s.name',
                DB::raw('SUM(payments.platform_fee_amount) as commission'),
                DB::raw('COUNT(*) as payment_rows')
            )
            ->groupBy('i_s_p_s.id', 'i_s_p_s.name')
            ->orderByDesc('commission')
            ->get();

        $totals = [
            'gross_revenue' => (float) Payment::query()->where('status', 'completed')->sum('amount'),
            'commission_accrued' => (float) Payment::query()->where('status', 'completed')->sum('platform_fee_amount'),
        ];

        $ledger = Payment::query()
            ->where('status', 'completed')
            ->whereNotNull('platform_fee_amount')
            ->where('platform_fee_amount', '>', 0)
            ->with(['isp', 'customer'])
            ->latest(DB::raw('COALESCE(processed_at, created_at)'))
            ->paginate(25, ['*'], 'ledger_page')
            ->appends($request->except('ledger_page'));

        $payouts = IspPayout::query()
            ->with(['isp', 'creator', 'approver'])
            ->latest()
            ->paginate(12, ['*'], 'payouts_page')
            ->appends($request->except('payouts_page'));

        $isps = ISP::query()->orderBy('name')->get(['id', 'name', 'currency']);

        $payoutTotalsByStatus = IspPayout::query()
            ->select('status', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $saasBillingForecast = ISP::query()
            ->whereNotNull('saas_next_billing_at')
            ->orderBy('saas_next_billing_at')
            ->limit(60)
            ->get(['id', 'name', 'company_name', 'saas_next_billing_at', 'saas_subscription_status', 'currency']);

        return view('admin.platform-finance.index', [
            'commissionPercent' => $commissionPercent,
            'months' => $months,
            'monthlyFlow' => $monthlyFlow,
            'revenueByIsp' => $revenueByIsp,
            'maxIspBar' => $maxIspBar,
            'commissionByIsp' => $commissionByIsp,
            'totals' => $totals,
            'ledger' => $ledger,
            'payouts' => $payouts,
            'isps' => $isps,
            'payoutTotalsByStatus' => $payoutTotalsByStatus,
            'saasBillingForecast' => $saasBillingForecast,
        ]);
    }

    public function updateCommission(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'platform_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        PlatformSetting::put('platform_commission_percent', (string) $validated['platform_commission_percent']);

        return back()->with('success', 'Commission rate saved. Fees are calculated when a payment is marked completed.');
    }

    public function storePayout(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'isp_id' => ['required', 'exists:i_s_p_s,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:8'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $isp = ISP::query()->findOrFail($validated['isp_id']);

        IspPayout::query()->create([
            'isp_id' => $validated['isp_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? ($isp->currency ?: 'KES'),
            'status' => IspPayout::STATUS_PENDING,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payout draft recorded (pending). Approve or mark paid when funds are sent.');
    }

    public function updatePayoutStatus(Request $request, IspPayout $payout): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', [
                IspPayout::STATUS_PENDING,
                IspPayout::STATUS_APPROVED,
                IspPayout::STATUS_PAID,
                IspPayout::STATUS_REJECTED,
            ])],
        ]);

        $status = $validated['status'];
        $updates = ['status' => $status];

        if ($status === IspPayout::STATUS_APPROVED) {
            $updates['approved_at'] = now();
            $updates['approved_by'] = $request->user()->id;
            $updates['paid_at'] = null;
        } elseif ($status === IspPayout::STATUS_PAID) {
            $updates['paid_at'] = now();
            if (! $payout->approved_at) {
                $updates['approved_at'] = now();
                $updates['approved_by'] = $request->user()->id;
            }
        } elseif ($status === IspPayout::STATUS_PENDING || $status === IspPayout::STATUS_REJECTED) {
            $updates['approved_at'] = null;
            $updates['approved_by'] = null;
            $updates['paid_at'] = null;
        }

        $payout->update($updates);

        return back()->with('success', 'Payout status updated.');
    }
}
