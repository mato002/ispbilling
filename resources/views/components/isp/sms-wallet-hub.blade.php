@props([
    'isp',
    'topupAction' => null,
])

@php
    $rate = (float) config('sms.unit_cost', 0.60);
    $currency = strtoupper($isp->currency ?? 'KES');
    $currencySymbol = config("sms.currency_symbols.{$currency}", $currency);
    $balance = (float) ($isp->sms_balance ?? 0);
    $smsCredits = $rate > 0 ? (int) floor($balance / $rate) : 0;

    $rawPhone = $isp->saas_billing_phone ?? $isp->phone ?? '';
    $digits = preg_replace('/\D/', '', (string) $rawPhone);
    if (str_starts_with($digits, '254')) {
        $localPhone = substr($digits, 3);
    } elseif (str_starts_with($digits, '0')) {
        $localPhone = substr($digits, 1);
    } else {
        $localPhone = $digits;
    }

    $topupAction = $topupAction ?? route('isp.settings.sms-wallet.topup');
@endphp

<div
    class="isp-sms-wallet-hub pb-card isp-settings-panel overflow-hidden"
    x-data="{
        modalOpen: false,
        submitting: false,
        rate: {{ json_encode($rate) }},
        currencySymbol: {{ json_encode($currencySymbol) }},
        amount: 1000,
        quickAmounts: [500, 1000, 5000],
        get smsYield() {
            const amt = parseFloat(this.amount) || 0;
            return this.rate > 0 ? Math.floor(amt / this.rate) : 0;
        },
        get yieldLabel() {
            const amt = (parseFloat(this.amount) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            const count = this.smsYield.toLocaleString();
            return `Rate: ${this.currencySymbol} ${this.rate.toFixed(2)} per SMS. Staking ${this.currencySymbol} ${amt} yields ${count} system messages.`;
        },
        addQuick(n) {
            this.amount = (parseFloat(this.amount) || 0) + n;
        },
        openTopUp() {
            this.modalOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closeTopUp() {
            this.modalOpen = false;
            document.body.classList.remove('overflow-hidden');
        },
    }"
    @keydown.escape.window="if (modalOpen) closeTopUp()"
    @if ($errors->has('phone') || $errors->has('amount'))
        x-init="openTopUp()"
    @endif
>
    <div class="isp-sms-wallet-hub__hero relative overflow-hidden px-4 py-5 sm:px-6 sm:py-6">
        <div class="isp-sms-wallet-hub__hero-glow" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <span class="isp-sms-wallet-hub__icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-emerald-200/80 bg-white/90 text-emerald-600 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <path d="M8 10h.01M12 10h.01M16 10h.01"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-emerald-700/80 dark:text-emerald-300/80">SMS wallet</p>
                    <p class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                        {{ $currencySymbol }} {{ number_format($balance, 2) }}
                        <span class="text-base font-semibold text-slate-500 dark:text-slate-400">remaining balance</span>
                    </p>
                    <p class="mt-1.5 text-sm font-medium text-emerald-800/90 dark:text-emerald-200/90">
                        ~{{ number_format($smsCredits) }} SMS credits
                        <span class="font-normal text-slate-500 dark:text-slate-400">at {{ $currencySymbol }} {{ number_format($rate, 2) }}/msg</span>
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="pb-btn pb-btn-success shrink-0 px-5 py-2.5 text-sm font-bold shadow-lg shadow-emerald-500/20"
                @click="openTopUp()"
            >
                Top-up SMS Wallet
            </button>
        </div>
    </div>

    <div
        x-show="modalOpen"
        x-cloak
        class="isp-sms-wallet-modal fixed inset-0 z-[120] flex items-end justify-center sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="isp-sms-topup-title"
    >
        <div
            class="isp-sms-wallet-modal__backdrop absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]"
            @click="closeTopUp()"
        ></div>

        <div
            class="isp-sms-wallet-modal__panel relative z-10 m-0 flex max-h-[min(92vh,640px)] w-full max-w-md flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-[var(--pb-panel)] shadow-2xl dark:border-slate-700 sm:m-4 sm:rounded-2xl"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            @click.stop
        >
            <div class="flex items-start justify-between gap-3 border-b border-[var(--pb-border)] px-5 py-4">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-400">Secure top-up</p>
                    <h3 id="isp-sms-topup-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-white">SMS wallet M-Pesa purchase</h3>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" @click="closeTopUp()" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ $topupAction }}" class="flex min-h-0 flex-1 flex-col" @submit="submitting = true">
                @csrf
                <div class="space-y-5 overflow-y-auto px-5 py-5">
                    <div>
                        <label class="isp-form-label" for="sms_topup_phone">Phone number</label>
                        <x-form-input-affix
                            name="phone"
                            id="sms_topup_phone"
                            type="tel"
                            prefix="+254"
                            :value="old('phone', $localPhone)"
                            placeholder="712345678"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                            class="isp-form-input--emerald"
                        />
                        @error('phone')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="isp-form-label" for="sms_topup_amount">Purchase amount ({{ $currencySymbol }})</label>
                        <div class="pb-input-affix">
                            <div class="flex w-full overflow-hidden rounded-xl border border-slate-200 bg-white transition-all duration-150 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-900/60">
                                <input
                                    type="number"
                                    name="amount"
                                    id="sms_topup_amount"
                                    min="10"
                                    step="1"
                                    x-model.number="amount"
                                    value="{{ old('amount', 1000) }}"
                                    required
                                    class="isp-form-input--emerald min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:ring-0"
                                >
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="quick in quickAmounts" :key="quick">
                                <button type="button" class="isp-sms-wallet-pill" @click="addQuick(quick)" x-text="'+' + currencySymbol + ' ' + quick.toLocaleString()"></button>
                            </template>
                        </div>
                        @error('amount')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-auto border-t border-[var(--pb-border)] bg-slate-50/80 px-5 py-4 dark:bg-slate-900/40">
                    <p class="text-center text-xs leading-relaxed text-slate-600 dark:text-slate-300" x-text="yieldLabel"></p>
                    <button type="submit" class="pb-btn pb-btn-success mt-4 w-full justify-center py-3 text-sm font-bold tracking-tight" :disabled="submitting">
                        <span x-show="!submitting">Initiate Secure M-Pesa STK Push</span>
                        <span x-show="submitting" x-cloak>Sending STK prompt…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
