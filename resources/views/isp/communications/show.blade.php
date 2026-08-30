@extends('layouts.admin')

@section('title', $campaign->title)

@section('header')
    <x-isp-page-header
        :title="$campaign->title"
        module="Communications"
        :description="'Status: '.ucfirst($campaign->status).' · Channel: '.strtoupper($campaign->channel)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.communications.index') }}" class="pb-btn">All campaigns</a>
            @if ($campaign->isLaunchable())
                <form method="POST" action="{{ route('isp.communications.launch', $campaign) }}" class="inline" onsubmit="return confirm('Queue this campaign for delivery?');">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Launch now</button>
                </form>
            @endif
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ session('error') }}</div>
        @endif

        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-dashboard.kpi-card title="Queued" :value="number_format($campaign->queued_count)" subtext="Outbound messages" iconBg="from-indigo-500 to-violet-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Sent" :value="number_format($campaign->sent_count)" subtext="Delivered" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 5 5L20 7"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Failed" :value="number_format($campaign->failed_count)" subtext="After retries" iconBg="from-rose-500 to-red-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Status" :value="ucfirst($campaign->status)" :subtext="$campaign->scheduled_at?->format('M j, H:i') ?? '—'" iconBg="from-amber-500 to-orange-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        <x-form-section title="Message">
            <p class="whitespace-pre-wrap text-sm text-slate-800 dark:text-slate-100">{{ $campaign->message_body }}</p>
            @if ($campaign->last_error)
                <p class="mt-3 text-sm text-rose-700 dark:text-rose-300">{{ $campaign->last_error }}</p>
            @endif
        </x-form-section>

        <x-form-section title="Audience filters">
            @if (empty($filterLabels))
                <p class="text-sm text-slate-500">All subscribers with deliverable contacts for the selected channel.</p>
            @else
                <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ($filterLabels as $key => $label)
                        <div>
                            <dt class="isp-form-label mb-1">{{ ucfirst($key) }}</dt>
                            <dd class="text-sm">{{ $label }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-form-section>

        <x-form-section title="Audience preview" description="First 50 resolved recipients.">
            @if ($preview->isEmpty())
                <p class="text-sm text-slate-500">No recipients matched at launch time.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview as $row)
                            <tr>
                                <td>{{ $row['full_name'] }}</td>
                                <td class="font-mono text-xs">{{ $row['phone_number'] ?? '—' }}</td>
                                <td class="text-xs">{{ $row['email'] ?? '—' }}</td>
                                <td>{{ strtoupper($row['connection_type'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>

        <x-form-section title="Delivery logs" description="Latest queue records with gateway responses.">
            @if ($deliveryLogs->isEmpty())
                <p class="text-sm text-slate-500">No queue records yet.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Channel</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Retries</th>
                            <th>Sent at</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveryLogs as $log)
                            <tr>
                                <td>{{ $log->customer?->full_name ?? '—' }}</td>
                                <td><span class="pb-pill">{{ strtoupper($log->channel) }}</span></td>
                                <td class="font-mono text-xs">{{ $log->recipient_phone ?? $log->recipient_email ?? '—' }}</td>
                                <td>
                                    @if ($log->status === 'sent')
                                        <x-status-badge status="sent" />
                                    @elseif ($log->status === 'failed')
                                        <x-status-badge status="failed" />
                                    @else
                                        <x-status-badge status="pending" />
                                    @endif
                                </td>
                                <td>{{ $log->retry_count }}</td>
                                <td class="whitespace-nowrap text-xs">{{ $log->sent_at?->format('M j, H:i') ?? '—' }}</td>
                                <td class="max-w-xs truncate text-xs text-slate-500" title="{{ $log->gateway_response }}">{{ \Illuminate\Support\Str::limit($log->gateway_response, 60) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>
    </div>
@endsection
