@extends('layouts.admin')

@section('title', 'Customer Usage')

@section('header')
    <x-isp-page-header
        :title="$customer->full_name"
        module="Data Usage"
        description="Upload, download, and session breakdown for this subscriber."
        primaryLabel="Customer profile"
        :primaryHref="route('isp.customers.show', $customer)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.data-usage.index') }}" class="pb-btn">Back to usage</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @php
        $totals = $breakdown['totals'];
        $daily = $breakdown['daily'];
        $monthly = $breakdown['monthly'];
        $currentSession = $breakdown['current_session'];
        $maxDaily = max(1, (int) $daily->max('total_bytes'));
    @endphp

    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-dashboard.kpi-card title="Total (30 days)" :value="\App\Models\DataUsage::formatBytes($totals['total'])" subtext="Monitoring only" iconBg="from-indigo-500 to-violet-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3 9 4-18 3 9h4"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Upload" :value="\App\Models\DataUsage::formatBytes($totals['upload'])" subtext="Upstream" iconBg="from-cyan-500 to-blue-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Download" :value="\App\Models\DataUsage::formatBytes($totals['download'])" subtext="Downstream" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Last active" :value="$totals['last_active']?->format('M j, H:i') ?? '—'" subtext="Latest usage record" iconBg="from-amber-500 to-orange-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        @if ($currentSession)
            <x-form-section title="Current session" description="Live hotspot session (not yet finalized in usage history).">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="isp-form-label mb-1">Upload</dt>
                        <dd class="text-sm font-medium">{{ $currentSession->formatted_uploaded }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Download</dt>
                        <dd class="text-sm font-medium">{{ $currentSession->formatted_downloaded }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Total</dt>
                        <dd class="text-sm font-medium">{{ $currentSession->formatted_total_bytes }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">IP</dt>
                        <dd class="font-mono text-sm">{{ $currentSession->ip_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">MAC</dt>
                        <dd class="font-mono text-sm">{{ $currentSession->mac_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Started</dt>
                        <dd class="text-sm">{{ $currentSession->started_at?->format('M j, Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-form-section>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-form-section title="Daily usage (30 days)">
                @if ($daily->isEmpty())
                    <p class="text-sm text-slate-500">No daily usage recorded yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($daily as $day)
                            @php $pct = min(100, round(((int) $day->total_bytes / $maxDaily) * 100)); @endphp
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-slate-600">
                                    <span>{{ \Illuminate\Support\Carbon::parse($day->usage_date)->format('M j') }}</span>
                                    <span>{{ \App\Models\DataUsage::formatBytes((int) $day->total_bytes) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-form-section>

            <x-form-section title="Monthly usage">
                @if ($monthly->isEmpty())
                    <p class="text-sm text-slate-500">No monthly aggregates yet.</p>
                @else
                    <x-data-table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Upload</th>
                                <th>Download</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $month)
                                <tr>
                                    <td>{{ $month->usage_month }}</td>
                                    <td>{{ \App\Models\DataUsage::formatBytes((int) $month->upload_bytes) }}</td>
                                    <td>{{ \App\Models\DataUsage::formatBytes((int) $month->download_bytes) }}</td>
                                    <td class="font-medium">{{ \App\Models\DataUsage::formatBytes((int) $month->total_bytes) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data-table>
                @endif
            </x-form-section>
        </div>

        <x-form-section title="Recent usage records" description="Individual session and poll records stored for monitoring.">
            @if ($recentRecords->isEmpty())
                <p class="text-sm text-slate-500">No records yet. Usage is captured when sessions end or during router polls.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Recorded</th>
                            <th>Source</th>
                            <th>Router</th>
                            <th>Package</th>
                            <th>Upload</th>
                            <th>Download</th>
                            <th>Total</th>
                            <th>IP / MAC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentRecords as $record)
                            <tr>
                                <td class="whitespace-nowrap">{{ $record->recorded_at->format('M j, H:i') }}</td>
                                <td><span class="pb-pill">{{ strtoupper($record->source) }}</span></td>
                                <td>{{ $record->router?->name ?? '—' }}</td>
                                <td>{{ $record->plan?->name ?? '—' }}</td>
                                <td>{{ $record->formatted_upload_bytes }}</td>
                                <td>{{ $record->formatted_download_bytes }}</td>
                                <td class="font-medium">{{ $record->formatted_total_bytes }}</td>
                                <td class="font-mono text-xs">
                                    {{ $record->ip_address ?? '—' }}
                                    @if ($record->mac_address)
                                        <br>{{ $record->mac_address }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>
    </div>
@endsection
