@extends('layouts.admin')

@section('title', 'Users · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $S = $ispSettingsShell; @endphp

    <x-isp-settings-workspace
        meta-title="User management"
>
        <div class="flex flex-wrap items-center justify-end gap-2">
            @if ($canManageUsers)
                <a href="{{ route('isp.settings.roles') }}" class="pb-btn">Roles &amp; permissions</a>
                <a href="{{ route('isp.settings.users.create') }}" class="pb-btn pb-btn-primary">Add user</a>
            @endif
        </div>

        <div class="pb-card isp-settings-panel overflow-hidden p-4 sm:p-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-dashed border-[var(--pb-border)] pb-4">
                <div>
                    <h3 class="isp-settings-section-title">Workspace operators</h3>
</div>
            </div>

            @if ($S)
                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Active admins</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $S['stats']['admin_users'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Staff role</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Configurable</p>
</div>
                    <div class="rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Last settings change</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $S['last_settings_human'] ?? 'Not recorded' }}</p>
                    </div>
                </div>
            @endif

            <div class="isp-table-wrap">
                <table class="isp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</td>
                                <td class="text-slate-600 dark:text-slate-300">{{ $user->email }}</td>
                                <td>
                                    <span class="pb-pill pb-pill-info text-[10px]">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="pb-pill pb-pill-ok text-[10px]">Active</span>
                                    @else
                                        <span class="pb-pill pb-pill-bad text-[10px]">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($canManageUsers)
                                        <a href="{{ route('isp.settings.users.edit', $user) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-300">Edit</a>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-sm text-slate-500 dark:text-slate-400">No users found for this ISP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-isp-settings-workspace>
@endsection
