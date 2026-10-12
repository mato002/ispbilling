@extends('hotspot.layout')

@section('content')
<div class="max-w-full mx-auto px-1 sm:px-4">
    <div class="text-center mb-3 sm:mb-6">
        <div class="inline-block p-2 sm:p-3 bg-blue-100 rounded-full mb-1 sm:mb-4">
            <svg class="w-8 h-8 sm:w-16 sm:h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
        </div>
        <h2 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">Complete Purchase</h2>
        <p class="text-xs sm:text-base text-gray-600">Get connected instantly</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-6 mb-3 sm:mb-6 border border-gray-200">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <h3 class="text-[10px] sm:text-sm font-medium uppercase tracking-wide text-gray-500">Selected package</h3>
                <span class="block truncate font-semibold text-sm sm:text-base text-gray-900">{{ $plan->name }}</span>
            </div>
            <span class="shrink-0 font-bold text-blue-600 text-lg sm:text-xl">Ksh {{ number_format($plan->price, 0) }}</span>
        </div>
        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs sm:text-sm text-gray-600">
            @if($plan->data_limit_mb)
                <span>{{ round($plan->data_limit_mb/1024, 1) }} GB data</span>
            @endif
            @if($plan->download_speed_kbps)
                <span>{{ round($plan->download_speed_kbps/1024) }} Mbps</span>
            @endif
            @if($plan->duration_days)
                <span>{{ $plan->duration_days }} days</span>
            @endif
        </div>
    </div>

    <form id="hotspot-purchase-form" method="POST" action="{{ route('hotspot.process-purchase', ['plan' => $plan->id]) }}" class="space-y-2 sm:space-y-4">
        @csrf
        <input type="hidden" name="router_ip" value="{{ request()->get('router_ip') ?? request()->ip() }}">
        
        <div>
            <label for="phone_number" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" required
                   class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   inputmode="tel" autocomplete="tel" placeholder="07XX XXX XXX">
        </div>

        <button type="submit" 
                class="w-full py-3 sm:py-4 px-4 sm:px-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:from-blue-700 hover:to-indigo-700 transition duration-150 text-sm sm:text-base">
            Pay Ksh {{ number_format($plan->price, 0) }} with M-Pesa
        </button>
        <div id="purchase-status" class="hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></div>
    </form>

    <div class="mt-3 rounded-lg border border-gray-200 bg-white p-3 sm:mt-5 sm:p-4">
        <p class="mb-2 text-xs font-semibold text-gray-700">Have a voucher? Enter it here</p>
        <form method="POST" action="{{ route('hotspot.process-voucher') }}" class="grid grid-cols-[1fr_auto] gap-2">
            @csrf
            <input type="hidden" name="router_ip" value="{{ request()->get('router_ip') ?? request()->ip() }}">
            <label for="voucher_code" class="sr-only">12-character voucher code</label>
            <input id="voucher_code" name="voucher_code" type="text" required maxlength="12" minlength="12"
                   value="{{ old('voucher_code') }}" autocomplete="off" autocapitalize="characters"
                   oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                   placeholder="Enter 12-character code"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-center font-mono uppercase tracking-widest focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600">Redeem</button>
        </form>
    </div>

    <div class="mt-3 sm:mt-6 text-center">
        <a href="{{ route('hotspot.splash', ['router_ip' => request()->ip()]) }}" 
           class="text-blue-600 hover:text-blue-800 font-medium">
            ← Back to Packages
        </a>
    </div>

    <div class="mt-3 pt-3 sm:mt-8 sm:pt-6 border-t border-gray-200">
        <p class="text-xs text-gray-500 text-center">
            Payment will be processed via M-PESA. You'll receive an STK push to complete the transaction.
        </p>
    </div>
</div>
<script>
    document.getElementById('hotspot-purchase-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        const form = this;
        const button = form.querySelector('button[type="submit"]');
        const status = document.getElementById('purchase-status');
        const token = form.querySelector('input[name="_token"]').value;
        const phoneNumber = form.querySelector('input[name="phone_number"]').value;
        const originalText = button.textContent.trim();
        let invoiceId = form.dataset.invoiceId || null;

        const showStatus = (message, ok) => {
            status.textContent = message;
            status.className = 'rounded-lg px-3 py-2 text-sm ' + (ok ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800');
        };

        button.disabled = true;
        button.textContent = invoiceId ? 'Sending M-Pesa prompt…' : 'Preparing payment…';
        status.className = 'hidden';

        try {
            if (!invoiceId) {
                const purchaseResponse = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: new FormData(form)
                });
                const purchase = await purchaseResponse.json();
                if (!purchaseResponse.ok || !purchase.success) {
                    throw new Error(purchase.message || Object.values(purchase.errors || {}).flat()[0] || 'Could not prepare the purchase.');
                }
                invoiceId = purchase.invoice_id;
                form.dataset.invoiceId = invoiceId;
            }

            if (form.dataset.statusUrl) {
                const resumed = await monitorPayment(form.dataset.statusUrl);
                if (!resumed) {
                    button.disabled = false;
                    button.textContent = 'Check payment status';
                }
                return;
            }

            const paymentResponse = await fetch('{{ route('customer.payments.initiate') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': token
                },
                body: new URLSearchParams({
                    _token: token,
                    invoice_id: invoiceId,
                    payment_method: 'mpesa',
                    phone_number: phoneNumber
                })
            });
            const payment = await paymentResponse.json();
            if (!paymentResponse.ok || !payment.success) {
                throw new Error(payment.message || 'Could not send the M-Pesa prompt. Check the number and try again.');
            }

            if (!payment.status_url) {
                throw new Error('The payment started, but payment verification could not be opened. Contact support.');
            }
            form.dataset.statusUrl = payment.status_url;
            const monitored = await monitorPayment(payment.status_url);
            if (!monitored) {
                button.disabled = false;
                button.textContent = 'Check payment status';
            }
        } catch (error) {
            const message = error.message || 'Payment could not be started. Please try again.';
            showStatus(message, false);
            if (window.Swal) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Could not start payment',
                    text: message,
                    confirmButtonText: 'Try again',
                    confirmButtonColor: '#087a48'
                });
            }
            button.disabled = false;
            button.textContent = originalText;
        }

        async function monitorPayment(statusUrl) {
            const safePhone = phoneNumber.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
            let closedByUser = false;
            const modalHtml = '<div><div style="display:inline-block;background:#087a48;color:#fff;font-weight:800;letter-spacing:.08em;padding:7px 14px;border-radius:7px;margin-bottom:14px">M-PESA</div>' +
                '<p style="margin:0 0 8px;color:#334155">Pay <strong>KSh {{ number_format($plan->price, 0) }}</strong> for <strong>{{ $plan->name }}</strong></p>' +
                '<p style="margin:0 0 16px;color:#64748b">A secure STK prompt was sent to <strong>' + safePhone + '</strong>.</p>' +
                '<div style="display:flex;align-items:center;justify-content:center;gap:10px;color:#087a48;font-weight:600"><span class="mpesa-spinner"></span> Verifying payment</div>' +
                '<p style="margin:16px 0 0;color:#64748b;font-size:13px">Enter your M-PESA PIN on your phone. This page will update automatically.</p></div>';
            const modal = window.Swal ? Swal.fire({
                title: 'M-PESA payment',
                html: modalHtml,
                showCancelButton: true,
                showConfirmButton: false,
                cancelButtonText: 'Close',
                cancelButtonColor: '#64748b',
                allowOutsideClick: false,
                customClass: { popup: 'hotspot-payment-modal' }
            }) : Promise.resolve();
            modal.then(() => { closedByUser = true; });

            button.disabled = true;
            button.textContent = 'Verifying payment…';
            const deadline = Date.now() + 120000;
            while (!closedByUser && Date.now() < deadline) {
                await new Promise(resolve => setTimeout(resolve, 5000));
                if (closedByUser) break;
                try {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error('Status request failed');
                    const result = await response.json();
                    if (result.status === 'completed') {
                        if (window.Swal) await Swal.fire({
                            icon: 'success',
                            title: 'Payment confirmed',
                            text: 'Payment received. Connecting your device now…',
                            confirmButtonText: 'Continue',
                            confirmButtonColor: '#087a48',
                            timer: 2500,
                            timerProgressBar: true
                        });
                        if (result.access_url) window.location.assign(result.access_url);
                        return true;
                    }
                    if (['failed', 'cancelled'].includes(result.status)) {
                        form.dataset.statusUrl = '';
                        if (window.Swal) await Swal.fire({
                            icon: 'error',
                            title: 'Payment not completed',
                            text: result.message || 'The M-PESA request was cancelled or declined.',
                            confirmButtonText: 'Try again',
                            confirmButtonColor: '#087a48'
                        });
                        showStatus(result.message || 'Payment was cancelled or declined. You can try again.', false);
                        button.disabled = false;
                        button.textContent = originalText;
                        return true;
                    }
                } catch (error) {
                    // Continue polling through brief network interruptions.
                }
            }

            if (window.Swal && !closedByUser) {
                const choice = await Swal.fire({
                    icon: 'warning',
                    title: 'Still waiting for M-PESA',
                    text: 'We have not received confirmation yet. If you paid, keep checking for the callback.',
                    showCancelButton: true,
                    confirmButtonText: 'Keep checking',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#087a48'
                });
                if (choice.isConfirmed) return monitorPayment(statusUrl);
            }
            showStatus('The payment request is still active. Tap “Check payment status” to check again.', true);
            return false;
        }
    });
</script>
@endsection
