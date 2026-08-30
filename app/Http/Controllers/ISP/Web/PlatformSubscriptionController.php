<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\PlatformSaasInvoice;
use App\Models\PlatformSaasPlan;
use App\Services\ActivityLogService;
use App\Services\PlatformSaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSubscriptionController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);

        $isp = ISP::query()
            ->with([
                'saasPlan',
                'pendingSaasPlan',
                'platformSaasInvoices' => fn ($q) => $q->latest()->limit(24),
            ])
            ->findOrFail($user->isp_id);

        $availablePlans = PlatformSaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('isp.platform-subscription.index', compact('isp', 'availablePlans'));
    }

    public function changePlan(Request $request, PlatformSaasBillingService $billing): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);
        abort_unless($user->isIspAdmin() || $user->isSuperAdmin(), 403);

        $data = $request->validate([
            'platform_saas_plan_id' => ['required', 'exists:platform_saas_plans,id'],
        ]);

        $isp = ISP::query()->with('saasPlan')->findOrFail($user->isp_id);
        $plan = PlatformSaasPlan::query()->where('is_active', true)->findOrFail($data['platform_saas_plan_id']);

        try {
            $result = $billing->changePlan($isp, $plan);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('isp.platform-subscription.index')
                ->with('error', $e->getMessage());
        }

        try {
            ActivityLogService::logAction(
                'saas_plan_change',
                $result['message'],
                ['plan_id' => $plan->id, 'applied' => $result['applied']]
            );
        } catch (\Throwable $e) {
            // Best-effort
        }

        if ($result['invoice']) {
            return redirect()->route('isp.platform-subscription.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('isp.platform-subscription.index')
            ->with('success', $result['message']);
    }

    public function payInvoice(PlatformSaasInvoice $invoice, PlatformSaasBillingService $billing): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id === $invoice->isp_id, 403);
        abort_unless($invoice->status === PlatformSaasInvoice::STATUS_PENDING, 400);

        $result = $billing->initiateStkForInvoice($invoice);

        if ($result['success'] ?? false) {
            return redirect()->route('isp.platform-subscription.index')
                ->with('success', 'M-Pesa STK push sent to your billing number. Approve the prompt on your phone to pay.');
        }

        return redirect()->route('isp.platform-subscription.index')
            ->with('error', $result['message'] ?? 'Could not start M-Pesa payment.');
    }

    public function downloadInvoice(PlatformSaasInvoice $invoice): View
    {
        $user = auth()->user();
        abort_unless($user->isp_id === $invoice->isp_id, 403);

        $invoice->load(['isp', 'plan']);

        return view('isp.platform-subscription.invoice-print', compact('invoice'));
    }
}
