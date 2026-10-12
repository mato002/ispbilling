@extends('hotspot.layout')

@section('content')
<div class="text-center">
    <button id="open-voucher-modal" type="button"
            class="mb-4 w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 sm:mb-6 sm:w-auto sm:min-w-72">
        🎫 Have a voucher? Redeem instantly
    </button>

    @if(!isset($portalSettings) || $portalSettings->show_packages)
    <div class="mb-3 sm:mb-8">
        <div class="inline-block p-2 sm:p-4 bg-blue-100 rounded-full mb-2 sm:mb-4">
            <svg class="w-9 h-9 sm:w-16 sm:h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
            </svg>
        </div>
        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Available Packages</h3>
    </div>
    @endif

    @if($plans->count() > 0 && (!isset($portalSettings) || $portalSettings->show_packages))
    <div class="mb-4 sm:mb-8">
        <div class="grid grid-cols-2 gap-2 sm:gap-4 lg:grid-cols-3">
            @foreach($plans as $plan)
            <div class="bg-white rounded-lg shadow-sm p-2 sm:p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="text-center">
                    <h4 class="text-xs leading-tight sm:text-lg font-bold text-gray-900 mb-1 sm:mb-2">{{ $plan->name }}</h4>
                    <div class="text-base sm:text-3xl font-bold text-blue-600 mb-1 sm:mb-2">
                        Ksh {{ number_format($plan->price, 0) }}
                    </div>
                    <div class="text-[11px] leading-tight sm:text-sm text-gray-600 mb-2 sm:mb-4">
                        @if($plan->data_limit_mb)
                            {{ round($plan->data_limit_mb/1024, 1) }} GB
                        @endif
                        @if($plan->download_speed_kbps)
                            • {{ round($plan->download_speed_kbps/1024) }} Mbps
                        @endif
                        @if($plan->duration_days)
                            • {{ $plan->duration_days }} days
                        @endif
                    </div>
                    @if(!isset($portalSettings) || $portalSettings->show_payment_button)
                    <button type="button"
                       data-plan-id="{{ $plan->id }}"
                       data-plan-name="{{ $plan->name }}"
                       data-plan-price="{{ number_format($plan->price, 0) }}"
                       data-purchase-url="{{ route('hotspot.process-purchase', ['plan' => $plan->id]) }}"
                       data-router-ip="{{ request()->get('router_ip') ?? request()->ip() }}"
                       data-router-id="{{ $router?->id }}"
                       data-mac="{{ request()->query('mac') }}"
                       data-link-login-only="{{ request()->query('link-login-only') }}"
                       class="buy-hotspot-package block w-full py-2 px-1 sm:py-3 sm:px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-md sm:rounded-lg hover:from-blue-700 hover:to-indigo-700 transition duration-150 text-xs sm:text-base">
                        {{ isset($portalSettings) ? $portalSettings->payment_button_text : 'Buy Now' }}
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-2 gap-2 sm:gap-4">
        <a href="{{ route('hotspot.login', ['router_ip' => request()->ip()]) }}" 
           class="col-span-2 block w-full py-2 px-2 sm:py-4 sm:px-6 text-white font-semibold rounded-lg bg-gray-600 hover:bg-gray-700 transition duration-150 text-xs sm:text-base">
            👤 Already have an account? Login
        </a>
    </div>

    <div class="mt-3 pt-3 sm:mt-8 sm:pt-6 border-t border-gray-200">
        <p class="text-[11px] sm:text-xs text-gray-500">
            By purchasing, you agree to our Terms of Service
        </p>
    </div>
</div>
<script>
    document.querySelectorAll('.buy-hotspot-package').forEach(button => {
        button.addEventListener('click', async function () {
            if (!window.Swal) {
                alert('The secure checkout dialog could not be loaded. Please refresh and try again.');
                return;
            }

            const buyButton = this;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const planName = buyButton.dataset.planName;
            const amount = buyButton.dataset.planPrice;
            const defaultButtonText = buyButton.dataset.defaultLabel || buyButton.textContent.trim();
            buyButton.dataset.defaultLabel = defaultButtonText;
            const escapeHtml = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');

            const startPayment = async phone => {
                const purchaseResponse = await fetch(buyButton.dataset.purchaseUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: new URLSearchParams({
                        _token: token,
                        router_ip: buyButton.dataset.routerIp,
                        router_id: buyButton.dataset.routerId,
                        mac: buyButton.dataset.mac,
                        link_login_only: buyButton.dataset.linkLoginOnly,
                        phone_number: phone
                    })
                });
                const purchase = await purchaseResponse.json();
                if (!purchaseResponse.ok || !purchase.success) {
                    throw new Error(purchase.message || Object.values(purchase.errors || {}).flat()[0] || 'Could not prepare your purchase.');
                }

                const paymentResponse = await fetch(@json(route('customer.payments.initiate')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-TOKEN': token
                    },
                    body: new URLSearchParams({
                        _token: token,
                        invoice_id: purchase.invoice_id,
                        payment_method: 'mpesa',
                        phone_number: phone
                    })
                });
                const payment = await paymentResponse.json();
                if (!paymentResponse.ok || !payment.success) {
                    throw new Error(payment.message || 'Could not send the M-Pesa prompt.');
                }
                if (!payment.status_url) throw new Error('Payment started, but verification could not be opened. Contact support.');
                return payment.status_url;
            };

            const checkPayment = async (statusUrl, phone) => {
                let closedByUser = false;
                const safePhone = escapeHtml(phone);
                const modal = Swal.fire({
                    title: 'M-PESA payment',
                    html: '<div><div style="display:inline-block;background:#087a48;color:#fff;font-weight:800;letter-spacing:.08em;padding:7px 14px;border-radius:7px;margin-bottom:14px">M-PESA</div>' +
                        '<p style="margin:0 0 8px;color:#334155">Pay <strong>KSh ' + escapeHtml(amount) + '</strong> for <strong>' + escapeHtml(planName) + '</strong></p>' +
                        '<p style="margin:0 0 16px;color:#64748b">An STK prompt was sent to <strong>' + safePhone + '</strong>.</p>' +
                        '<div style="display:flex;align-items:center;justify-content:center;gap:10px;color:#087a48;font-weight:600"><span class="mpesa-spinner"></span> Verifying payment</div>' +
                        '<p style="margin:16px 0 0;color:#64748b;font-size:13px">Enter your M-PESA PIN. This screen will update automatically.</p></div>',
                    showCancelButton: true,
                    showConfirmButton: false,
                    cancelButtonText: 'Close',
                    cancelButtonColor: '#64748b',
                    allowOutsideClick: false,
                    customClass: { popup: 'hotspot-payment-modal' }
                });
                modal.then(() => { closedByUser = true; });

                const deadline = Date.now() + 120000;
                while (!closedByUser && Date.now() < deadline) {
                    await new Promise(resolve => setTimeout(resolve, 5000));
                    if (closedByUser) break;
                    try {
                        const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) continue;
                        const result = await response.json();
                        if (result.status === 'completed') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Payment confirmed',
                                text: 'Payment received. Connecting your device now…',
                                confirmButtonColor: '#087a48',
                                timer: 2500,
                                timerProgressBar: true
                            });
                            if (result.access_url) window.location.assign(result.access_url);
                            return true;
                        }
                        if (['failed', 'cancelled'].includes(result.status)) {
                            buyButton.dataset.statusUrl = '';
                            buyButton.dataset.phone = '';
                            buyButton.textContent = defaultButtonText;
                            await Swal.fire({
                                icon: 'error',
                                title: 'Payment not completed',
                                text: result.message || 'The M-PESA request was cancelled or declined. You can try again.',
                                confirmButtonText: 'Try again',
                                confirmButtonColor: '#087a48'
                            });
                            return true;
                        }
                    } catch (error) {
                        // Continue checking if the connection briefly drops.
                    }
                }

                if (!closedByUser) {
                    const choice = await Swal.fire({
                        icon: 'warning',
                        title: 'Still waiting for M-PESA',
                        text: 'No confirmation has arrived yet. If you paid, continue checking for the callback.',
                        showCancelButton: true,
                        confirmButtonText: 'Keep checking',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#087a48'
                    });
                    if (choice.isConfirmed) return checkPayment(statusUrl, phone);
                }
                buyButton.dataset.statusUrl = statusUrl;
                buyButton.dataset.phone = phone;
                buyButton.textContent = 'Check payment';
                return false;
            };

            if (buyButton.dataset.statusUrl) {
                await checkPayment(buyButton.dataset.statusUrl, buyButton.dataset.phone);
                return;
            }

            const form = await Swal.fire({
                title: 'Buy ' + planName,
                html: '<div style="margin:0 0 14px;padding:12px;border-radius:10px;background:#f1f5f9;color:#334155">Package <strong>' + escapeHtml(planName) + '</strong><br>Amount <strong style="color:#087a48">KSh ' + escapeHtml(amount) + '</strong></div><p style="color:#64748b;margin:0 0 8px">Enter the M-PESA number to receive the payment prompt.</p>',
                input: 'tel',
                inputPlaceholder: '07XX XXX XXX',
                inputAttributes: {
                    inputmode: 'tel',
                    autocomplete: 'tel',
                    'aria-label': 'M-PESA phone number'
                },
                showCancelButton: true,
                confirmButtonText: 'Send STK prompt',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#087a48',
                cancelButtonColor: '#64748b',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async value => {
                    const phone = (value || '').trim();
                    const digits = phone.replace(/\D/g, '');
                    if (!/^(0[17]\d{8}|254[17]\d{8})$/.test(digits)) {
                        Swal.showValidationMessage('Enter a valid Kenyan M-PESA number, such as 07XX XXX XXX.');
                        return false;
                    }
                    try {
                        const statusUrl = await startPayment(phone);
                        return { statusUrl, phone };
                    } catch (error) {
                        Swal.showValidationMessage(error.message || 'Payment could not be started. Try again.');
                        return false;
                    }
                }
            });

            if (form.isConfirmed && form.value) {
                buyButton.disabled = true;
                const verified = await checkPayment(form.value.statusUrl, form.value.phone);
                buyButton.disabled = false;
                if (!verified) buyButton.textContent = 'Check payment';
            }
        });
    });

    document.getElementById('open-voucher-modal')?.addEventListener('click', async function () {
        if (!window.Swal) {
            window.location.href = @json(route('hotspot.redeem', ['router_ip' => request()->get('router_ip') ?? request()->ip()]));
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]').content;
        const routerIp = @json(request()->get('router_ip') ?? request()->ip());
        const result = await Swal.fire({
            title: 'Redeem your voucher',
            html: '<p style="color:#64748b;margin:0 0 14px">Enter the 12-character code to connect now.</p>',
            input: 'text',
            inputPlaceholder: 'XXXXXXXXXXXX',
            inputAttributes: {
                maxlength: 12,
                autocapitalize: 'characters',
                autocomplete: 'one-time-code',
                'aria-label': '12-character voucher code'
            },
            inputValidator: value => {
                const code = (value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                return code.length === 12 ? undefined : 'Enter the full 12-character voucher code.';
            },
            showCancelButton: true,
            confirmButtonText: 'Redeem & connect',
            cancelButtonText: 'Back to packages',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: async value => {
                const code = (value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                const body = new URLSearchParams({
                    _token: token,
                    router_ip: routerIp,
                    voucher_code: code
                });
                try {
                    const response = await fetch(@json(route('hotspot.process-voucher')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-CSRF-TOKEN': token
                        },
                        body
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        Swal.showValidationMessage(data.message || 'Voucher could not be redeemed. Check the code and try again.');
                        return false;
                    }
                    return data;
                } catch (error) {
                    Swal.showValidationMessage('Could not reach the server. Check your connection and try again.');
                    return false;
                }
            }
        });

        if (result.isConfirmed && result.value?.redirect_url) {
            await Swal.fire({
                icon: 'success',
                title: 'Voucher accepted',
                text: 'Connecting your device now…',
                confirmButtonColor: '#059669',
                timer: 1800,
                timerProgressBar: true
            });
            window.location.assign(result.value.redirect_url);
        }
    });
</script>
@endsection





