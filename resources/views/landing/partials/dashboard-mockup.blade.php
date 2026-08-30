<div class="pb-dashboard">
    <div class="pb-dashboard-bar">
        <span class="pb-dashboard-dot bg-red-400"></span>
        <span class="pb-dashboard-dot bg-amber-400"></span>
        <span class="pb-dashboard-dot bg-emerald-400"></span>
        <span class="ml-3 text-xs font-semibold text-slate-500">{{ config('app.name', 'PrimeBilling') }} · Operations</span>
        <span class="ml-auto flex items-center gap-1.5 text-xs font-medium text-emerald-600">
            <span class="pb-pulse-dot"></span> Live
        </span>
    </div>

    <div class="p-4 sm:p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Network overview</p>
                <p class="text-lg font-bold text-slate-900">12 of 12 routers online</p>
            </div>
            <div class="flex gap-2">
                <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Hotspot active</span>
                <span class="rounded-lg bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">PPPoE synced</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Revenue today</p>
                <p class="mt-0.5 text-lg font-extrabold text-slate-900">KSh 94.3K</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Active sessions</p>
                <p class="mt-0.5 text-lg font-extrabold text-slate-900">742</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Subscribers</p>
                <p class="mt-0.5 text-lg font-extrabold text-slate-900">1,286</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Collections</p>
                <p class="mt-0.5 text-lg font-extrabold text-emerald-600">96.2%</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 lg:col-span-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Revenue — last 7 days</p>
                    <p class="text-xs font-bold text-blue-600">KSh 612K total</p>
                </div>
                <div class="mt-4 flex h-20 items-end gap-2">
                    @foreach ([38, 58, 44, 70, 62, 55, 82] as $h)
                        <div class="pb-chart-bar flex-1" style="height: {{ $h }}%;"></div>
                    @endforeach
                </div>
                <div class="mt-2 flex justify-between text-[10px] font-medium text-slate-400">
                    <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                </div>
            </div>

            <div class="space-y-2 lg:col-span-2">
                <p class="text-xs font-semibold text-slate-500">Live sessions</p>
                <div class="pb-session-row">
                    <div>
                        <p class="font-semibold text-slate-800">Main_Router</p>
                        <p class="text-slate-400">42 online</p>
                    </div>
                    <span class="font-bold text-emerald-600">●</span>
                </div>
                <div class="pb-session-row">
                    <div>
                        <p class="font-semibold text-slate-800">John Doe</p>
                        <p class="text-slate-400">10.0.0.142 · 1h 24m</p>
                    </div>
                    <span class="text-slate-500">↓1.2 GB</span>
                </div>
                <div class="pb-session-row">
                    <div>
                        <p class="font-semibold text-slate-800">Guest_904</p>
                        <p class="text-slate-400">10.0.0.94 · 45m</p>
                    </div>
                    <span class="text-slate-500">↓300 MB</span>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/60 px-3 py-2.5">
            <div class="flex items-center gap-2 text-xs">
                <span class="rounded-full bg-emerald-500 px-2 py-0.5 font-bold text-white">M-Pesa</span>
                <span class="font-medium text-emerald-800">KES 2,500 received · Grace W. reconnected automatically</span>
            </div>
        </div>
    </div>
</div>
