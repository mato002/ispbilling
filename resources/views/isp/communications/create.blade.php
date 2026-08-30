@extends('layouts.admin')

@section('title', 'Create campaign')

@section('header')
    <x-isp-page-header
        title="Create campaign"
        module="Communications"
        description="Messages are queued and delivered by the background worker — not sent from this form directly."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.communications.index') }}" class="pb-btn">Back to campaigns</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <form
        method="POST"
        action="{{ route('isp.communications.store') }}"
        class="mx-auto max-w-4xl space-y-6"
        x-data="campaignComposer()"
    >
        @csrf

        <x-form-section title="Campaign content">
            <div class="space-y-4">
                <div>
                    <label class="isp-form-label" for="title">Title</label>
                    <input id="title" name="title" class="isp-form-input" value="{{ old('title') }}" required maxlength="255">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="message_body">Message</label>
                    <textarea id="message_body" name="message_body" rows="6" class="isp-form-input" required maxlength="5000">{{ old('message_body') }}</textarea>
@error('message_body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="channel">Channel</label>
                    <select id="channel" name="channel" class="isp-form-select max-w-xs" x-model="channel" required>
                        <option value="sms" @selected(old('channel', 'sms') === 'sms')>SMS only</option>
                        <option value="email" @selected(old('channel') === 'email')>Email only</option>
                        <option value="both" @selected(old('channel') === 'both')>SMS + Email</option>
                    </select>
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Audience filters" description="All filters are combined (AND). Leave blank to target all matching subscribers.">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="customer_id">Single customer (optional)</label>
                    <select id="customer_id" name="customer_id" class="isp-form-select">
                        <option value="">— All / use filters below —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                {{ $customer->full_name }} ({{ $customer->phone_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="plan_id">Plan</label>
                    <select id="plan_id" name="plan_id" class="isp-form-select">
                        <option value="">All plans</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
                                {{ $plan->name }} ({{ strtoupper($plan->access_type ?? 'n/a') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="router_id">Router</label>
                    <select id="router_id" name="router_id" class="isp-form-select">
                        <option value="">All routers</option>
                        @foreach ($routers as $router)
                            <option value="{{ $router->id }}" @selected((string) old('router_id') === (string) $router->id)>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="connection_type">Connection type</label>
                    <select id="connection_type" name="connection_type" class="isp-form-select">
                        <option value="">All types</option>
                        <option value="hotspot" @selected(old('connection_type') === 'hotspot')>Hotspot</option>
                        <option value="pppoe" @selected(old('connection_type') === 'pppoe')>PPPoE</option>
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="status">Customer status</label>
                    <select id="status" name="status" class="isp-form-select">
                        <option value="">Any status</option>
                        <option value="active" @selected(old('status') === 'active')>Active</option>
                        <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                        <option value="expired" @selected(old('status') === 'expired')>Expired</option>
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="sms_group_id">SMS group</label>
                    <select id="sms_group_id" name="sms_group_id" class="isp-form-select">
                        <option value="">No group filter</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('sms_group_id') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('isp.communications.groups.index') }}" class="mt-1 inline-block text-xs text-indigo-600">Manage groups</a>
                </div>
                <div class="md:col-span-2">
                    <label class="isp-form-label" for="search">Search (name, phone, email)</label>
                    <input id="search" name="search" class="isp-form-input" value="{{ old('search') }}" placeholder="Optional narrow search">
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-950/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">Audience preview</p>
                        <p class="text-xs text-indigo-800/80 dark:text-indigo-200/80" x-text="previewLabel">Click refresh to estimate recipients.</p>
                    </div>
                    <button type="button" class="pb-btn text-xs" @click="refreshPreview()" :disabled="previewLoading">
                        <span x-show="!previewLoading">Refresh count</span>
                        <span x-show="previewLoading" x-cloak>Calculating…</span>
                    </button>
                </div>
                <template x-if="previewSample.length">
                    <ul class="mt-3 space-y-1 text-xs text-slate-700 dark:text-slate-300">
                        <template x-for="row in previewSample" :key="row.customer_id">
                            <li x-text="`${row.full_name} · ${row.phone_number || row.email || 'no contact'}`"></li>
                        </template>
                    </ul>
                </template>
            </div>
        </x-form-section>

        <x-form-section title="Delivery">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="send_action" value="now" class="text-indigo-600" @checked(old('send_action', 'now') === 'now') required>
                        Send now (queue immediately)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="send_action" value="schedule" class="text-indigo-600" @checked(old('send_action') === 'schedule')>
                        Schedule for later
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="send_action" value="draft" class="text-indigo-600" @checked(old('send_action') === 'draft')>
                        Save as draft
                    </label>
                </div>
                <div>
                    <label class="isp-form-label" for="scheduled_at">Schedule datetime</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="isp-form-input max-w-sm" value="{{ old('scheduled_at') }}">
                    @error('scheduled_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-form-section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('isp.communications.index') }}" class="pb-btn">Cancel</a>
            <button type="submit" class="pb-btn pb-btn-primary">Create campaign</button>
        </div>
    </form>

    @push('scripts')
    <script>
        function campaignComposer() {
            return {
                channel: @json(old('channel', 'sms')),
                previewCount: null,
                previewSample: [],
                previewLoading: false,
                get previewLabel() {
                    if (this.previewCount === null) return 'Click refresh to estimate recipients.';
                    return `${this.previewCount.toLocaleString()} recipient(s) with deliverable contact for selected channel.`;
                },
                async refreshPreview() {
                    this.previewLoading = true;
                    try {
                        const form = this.$el;
                        const data = new FormData(form);
                        const res = await fetch(@json(route('isp.communications.preview-audience')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: data,
                        });
                        const json = await res.json();
                        this.previewCount = json.count ?? 0;
                        this.previewSample = json.sample ?? [];
                    } catch (e) {
                        this.previewCount = 0;
                        this.previewSample = [];
                    } finally {
                        this.previewLoading = false;
                    }
                },
            };
        }
    </script>
    @endpush
@endsection
