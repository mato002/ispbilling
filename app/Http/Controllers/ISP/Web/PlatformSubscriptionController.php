<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\PlatformSaasInvoice;
use App\Services\PlatformSaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformSubscriptionController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);

        $isp = ISP::query()
            ->with(['saasPlan', 'platformSaasInvoices' => fn ($q) => $q->latest()->limit(24)])
            ->findOrFail($user->isp_id);

        return view('isp.platform-subscription.index', compact('isp'));
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
