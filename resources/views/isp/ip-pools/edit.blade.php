@extends('layouts.admin')

@section('title', 'Edit '.$ipPool->name)

@section('header')
    <x-isp-page-header
        :title="'Edit '.$ipPool->name"
        module="Network"
        description="Update pool ranges and addressing metadata."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.ip-pools.show', $ipPool) }}" class="pb-btn">View pool</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @php
        $initialRanges = old('ranges', $ipPool->ranges ?: [['start' => '', 'end' => '']]);
    @endphp
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.ip-pools.update', $ipPool) }}" class="pb-card space-y-5 p-6" x-data="ipPoolRanges()">
            @csrf
            @method('PUT')

            <div>
                <label class="isp-form-label">Router</label>
                <p class="text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->router?->name ?? '—' }}</p>
                <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">Router cannot be changed after create.</p>
            </div>

            <div>
                <label class="isp-form-label" for="name">Pool name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $ipPool->name) }}" class="isp-form-input" required>
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="isp-form-label mb-0">IP ranges</label>
                    <button type="button" class="pb-btn text-xs" @click="addRange()">Add range</button>
                </div>
                <template x-for="(range, index) in ranges" :key="index">
                    <div class="mb-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="isp-form-label text-xs" :for="'start-' + index">Start IP</label>
                            <input type="text" class="isp-form-input font-mono" :name="'ranges[' + index + '][start]'" :id="'start-' + index" x-model="range.start" required>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="isp-form-label text-xs" :for="'end-' + index">End IP</label>
                                <input type="text" class="isp-form-input font-mono" :name="'ranges[' + index + '][end]'" :id="'end-' + index" x-model="range.end" required>
                            </div>
                            <button type="button" class="pb-btn mt-6 self-start text-rose-600" x-show="ranges.length > 1" @click="removeRange(index)">Remove</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="gateway_address">Gateway</label>
                    <input id="gateway_address" type="text" name="gateway_address" value="{{ old('gateway_address', $ipPool->gateway_address) }}" class="isp-form-input font-mono">
                </div>
                <div>
                    <label class="isp-form-label" for="netmask">Netmask</label>
                    <input id="netmask" type="text" name="netmask" value="{{ old('netmask', $ipPool->netmask) }}" class="isp-form-input font-mono">
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="lease_time">Lease time (seconds)</label>
                    <input id="lease_time" type="number" name="lease_time" value="{{ old('lease_time', $ipPool->lease_time ?? 300) }}" min="60" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="comment">Comment</label>
                    <input id="comment" type="text" name="comment" value="{{ old('comment', $ipPool->comment) }}" class="isp-form-input">
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.ip-pools.show', $ipPool) }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save changes</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function ipPoolRanges() {
            const initial = @json($initialRanges);
            return {
                ranges: Array.isArray(initial) && initial.length
                    ? initial.map(r => ({ start: r.start || '', end: r.end || '' }))
                    : [{ start: '', end: '' }],
                addRange() { this.ranges.push({ start: '', end: '' }); },
                removeRange(index) { if (this.ranges.length > 1) this.ranges.splice(index, 1); },
            };
        }
    </script>
    @endpush
@endsection
