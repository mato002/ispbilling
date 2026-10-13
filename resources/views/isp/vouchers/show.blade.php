@extends('layouts.admin')

@section('title', 'Voucher Details')

@section('header')
    <x-isp-page-header
        title="Voucher {{ $voucher->voucher_code }}"
        module="Hotspot"
        description="Created {{ $voucher->created_at->format('M j, Y H:i') }}"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.vouchers.index') }}" class="pb-btn">Back to list</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-form-section title="Voucher">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @if($voucher->status === 'active')
                        <x-status-badge status="active" />
                    @elseif($voucher->status === 'used')
                        <x-status-badge :status="'Used'" />
                    @elseif($voucher->status === 'expired')
                        <x-status-badge status="expired" />
                    @else
                        <x-status-badge :status="ucfirst($voucher->status)" />
                    @endif

                    @if($voucher->voucher_type === 'time')
                        <span class="pb-pill pb-pill-cyan">{{ ucfirst($voucher->voucher_type) }}</span>
                    @elseif($voucher->voucher_type === 'data')
                        <span class="pb-pill pb-pill-ok">{{ ucfirst($voucher->voucher_type) }}</span>
                    @else
                        <span class="pb-pill">{{ ucfirst($voucher->voucher_type) }}</span>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <dt class="isp-form-label mb-1">Voucher code</dt>
                        <dd class="font-mono text-lg font-semibold tracking-wide text-slate-900 dark:text-white">{{ $voucher->voucher_code }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Batch</dt>
                        <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $voucher->batch_reference ?? 'Single voucher' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Router</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            @if($voucher->router)
                                {{ $voucher->router->name }}
                                <span class="font-mono text-slate-500">({{ $voucher->router->ip_address }})</span>
                            @else
                                <span class="text-slate-400">No router assigned</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Plan</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            @if($voucher->plan)
                                {{ $voucher->plan->name }}
                                @if($voucher->plan->price)
                                    <span class="text-slate-500">· KES {{ number_format($voucher->plan->price, 0) }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">No plan assigned</span>
                            @endif
                        </dd>
                    </div>
                    @if($voucher->speed_profile)
                        <div>
                            <dt class="isp-form-label mb-1">Speed profile</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $voucher->speed_profile }}</dd>
                        </div>
                    @endif
                    @if($voucher->price)
                        <div>
                            <dt class="isp-form-label mb-1">Price</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">KES {{ number_format($voucher->price, 2) }}</dd>
                        </div>
                    @endif
                    @if($voucher->voucher_type === 'time' && $voucher->time_limit_hours)
                        <div>
                            <dt class="isp-form-label mb-1">Time limit</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $voucher->time_limit_hours }} hour{{ $voucher->time_limit_hours > 1 ? 's' : '' }}</dd>
                        </div>
                    @endif
                    @if($voucher->voucher_type === 'data' && $voucher->data_limit_mb)
                        <div>
                            <dt class="isp-form-label mb-1">Data limit</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ number_format($voucher->data_limit_mb / 1024, 2) }} GB</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="isp-form-label mb-1">Expires at</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            @if($voucher->expires_at)
                                {{ $voucher->expires_at->format('M d, Y H:i') }}
                                @if($voucher->expires_at->isPast())
                                    <span class="text-red-600 dark:text-red-400">(Expired)</span>
                                @endif
                            @else
                                <span class="text-slate-400">No expiration</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Created by</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            {{ $voucher->creator?->name ?? 'System' }}
                        </dd>
                    </div>
                    @if($voucher->notes)
                        <div class="md:col-span-2">
                            <dt class="isp-form-label mb-1">Notes</dt>
                            <dd class="text-sm text-slate-700 dark:text-slate-300">{{ $voucher->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </x-form-section>

            @if($voucher->status === 'used' && $voucher->usedByCustomer)
                <x-form-section title="Usage">
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="isp-form-label mb-1">Used by</dt>
                            <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ $voucher->usedByCustomer->full_name }}</dd>
                            <dd class="text-xs text-slate-500">{{ $voucher->usedByCustomer->phone_number }}</dd>
                        </div>
                        <div>
                            <dt class="isp-form-label mb-1">Used at</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $voucher->used_at->format('M d, Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </x-form-section>
            @endif
        </div>

        <div class="space-y-6">
            <x-form-section title="Actions">
                <div class="flex flex-col gap-2">
                    @if($voucher->status === 'active')
                        <form method="POST" action="{{ route('isp.vouchers.deactivate', $voucher) }}" class="w-full" onsubmit="return confirm('Are you sure you want to deactivate this voucher?');">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center border-red-200 bg-red-50 text-red-900 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100">Deactivate voucher</button>
                        </form>
                    @endif
                    <button type="button" onclick="copyVoucherCode(this, @js($voucher->voucher_code))" class="pb-btn w-full justify-center">Copy code</button>
                    <button type="button" onclick="window.print()" class="pb-btn w-full justify-center">Print</button>
                </div>
            </x-form-section>
        </div>
    </div>

    <script>
        function copyVoucherCode(btn, code) {
            navigator.clipboard.writeText(code).then(function() {
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-900');
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-900');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy voucher code');
            });
        }
    </script>
@endsection
