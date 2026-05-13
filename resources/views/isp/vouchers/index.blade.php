@extends('layouts.admin')

@section('title', 'Vouchers')

@section('header')
    <x-isp-page-header
        title="Vouchers"
        module="Sales &amp; access"
        description="Time and data vouchers tied to plans and routers."
        primaryLabel="Create voucher"
        :primaryHref="route('isp.vouchers.create')"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.vouchers.create-bulk') }}" class="pb-btn">Bulk create</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <x-filter-bar :action="route('isp.vouchers.index')">
        <div class="min-w-[130px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="used" @selected(request('status') === 'used')>Used</option>
                <option value="expired" @selected(request('status') === 'expired')>Expired</option>
            </select>
        </div>
        <div class="min-w-[160px] flex-1">
            <label class="isp-form-label">Router</label>
            <select name="router_id" class="isp-form-select">
                <option value="">All routers</option>
                @foreach($routers as $router)
                    <option value="{{ $router->id }}" @selected((string) request('router_id') === (string) $router->id)>{{ $router->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">Type</label>
            <select name="voucher_type" class="isp-form-select">
                <option value="">All</option>
                <option value="time" @selected(request('voucher_type') === 'time')>Time</option>
                <option value="data" @selected(request('voucher_type') === 'data')>Data</option>
                <option value="unlimited" @selected(request('voucher_type') === 'unlimited')>Unlimited</option>
            </select>
        </div>
        <div class="min-w-[180px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Code or notes…">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.vouchers.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($vouchers->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No vouchers"
                message="Create vouchers or sync from your sales workflow."
                ctaLabel="Create voucher"
                :ctaHref="route('isp.vouchers.create')"
            />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Plan</th>
                    <th>Router</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Used by</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vouchers as $voucher)
                    <tr>
                        <td class="font-mono font-semibold">{{ $voucher->voucher_code }}</td>
                        <td>
                            @if($voucher->plan)
                                <div class="font-medium text-slate-900 dark:text-white">{{ $voucher->plan->name }}</div>
                                <div class="text-slate-500 text-sm">KES {{ number_format($voucher->plan->price, 0) }}</div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>{{ $voucher->router?->name ?? '—' }}</td>
                        <td><span class="pb-pill">{{ ucfirst($voucher->voucher_type) }}</span></td>
                        <td>
                            @if($voucher->status === 'active')
                                <x-status-badge status="active" />
                            @elseif($voucher->status === 'used')
                                <span class="pb-pill">Used</span>
                            @else
                                <x-status-badge status="expired" />
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-sm">{{ $voucher->created_at->format('M j, Y H:i') }}</td>
                        <td>
                            @if($voucher->usedByCustomer)
                                <div class="font-medium">{{ $voucher->usedByCustomer->full_name }}</div>
                                <div class="text-sm text-slate-500">{{ $voucher->used_at?->format('M j, Y H:i') }}</div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.vouchers.show', $voucher) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                            @if($voucher->status === 'active')
                                <form method="POST" action="{{ route('isp.vouchers.deactivate', $voucher) }}" class="inline ml-3" onsubmit="return confirm('Deactivate this voucher?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400">Deactivate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $vouchers->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
