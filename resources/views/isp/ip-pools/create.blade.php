@extends('layouts.admin')

@section('title', 'Create IP Pool')

@section('header')
    <x-isp-page-header
        title="Create IP pool"
        module="Network"
        description="Define an address range and push it to RouterOS."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.ip-pools.index') }}" class="pb-btn">Back to pools</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.ip-pools.store') }}" class="pb-card space-y-5 p-6" x-data="ipPoolRanges()">
            @csrf

            <div>
                <label class="isp-form-label" for="router_id">Router</label>
                <select id="router_id" name="router_id" class="isp-form-select" required>
                    <option value="">Select router</option>
                    @foreach ($routers as $router)
                        <option value="{{ $router->id }}" @selected(old('router_id') == $router->id)>
                            {{ $router->name }} ({{ $router->ip_address }})
                        </option>
                    @endforeach
                </select>
                @error('router_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="name">Pool name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="isp-form-input" required>
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
                            <input
                                type="text"
                                class="isp-form-input font-mono"
                                :name="'ranges[' + index + '][start]'"
                                :id="'start-' + index"
                                x-model="range.start"
                                placeholder="192.168.10.2"
                                required
                            >
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="isp-form-label text-xs" :for="'end-' + index">End IP</label>
                                <input
                                    type="text"
                                    class="isp-form-input font-mono"
                                    :name="'ranges[' + index + '][end]'"
                                    :id="'end-' + index"
                                    x-model="range.end"
                                    placeholder="192.168.10.254"
                                    required
                                >
                            </div>
                            <button
                                type="button"
                                class="pb-btn mt-6 self-start text-rose-600"
                                x-show="ranges.length > 1"
                                @click="removeRange(index)"
                            >Remove</button>
                        </div>
                    </div>
                </template>
                @error('ranges')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('ranges.0.start')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('ranges.0.end')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="gateway_address">Gateway (optional)</label>
                    <input id="gateway_address" type="text" name="gateway_address" value="{{ old('gateway_address') }}" class="isp-form-input font-mono">
                    @error('gateway_address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="netmask">Netmask (optional)</label>
                    <input id="netmask" type="text" name="netmask" value="{{ old('netmask') }}" class="isp-form-input font-mono" placeholder="255.255.255.0">
                    @error('netmask')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="lease_time">Lease time (seconds)</label>
                    <input id="lease_time" type="number" name="lease_time" value="{{ old('lease_time', 300) }}" min="60" class="isp-form-input">
                    @error('lease_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="comment">Comment</label>
                    <input id="comment" type="text" name="comment" value="{{ old('comment') }}" class="isp-form-input">
                    @error('comment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.ip-pools.index') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Create pool</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function ipPoolRanges() {
            const oldRanges = @json(old('ranges', [['start' => '', 'end' => '']]));
            return {
                ranges: Array.isArray(oldRanges) && oldRanges.length
                    ? oldRanges.map(r => ({ start: r.start || '', end: r.end || '' }))
                    : [{ start: '', end: '' }],
                addRange() { this.ranges.push({ start: '', end: '' }); },
                removeRange(index) { if (this.ranges.length > 1) this.ranges.splice(index, 1); },
            };
        }
    </script>
    @endpush
@endsection
