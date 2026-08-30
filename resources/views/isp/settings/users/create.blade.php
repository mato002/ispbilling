@extends('layouts.admin')

@section('title', 'Add user · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    <x-isp-settings-workspace
        meta-title="Add workspace user"
>
        <form method="POST" action="{{ route('isp.settings.users.store') }}" class="pb-card isp-settings-panel p-4 sm:p-6">
            @csrf

            <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                <h3 class="isp-settings-section-title">Account details</h3>
</div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="name">Full name</label>
                    <input id="name" class="isp-form-input" type="text" name="name" value="{{ old('name') }}" required />
                    @error('name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="email">Email</label>
                    <input id="email" class="isp-form-input" type="email" name="email" value="{{ old('email') }}" required />
                    @error('email')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="phone">Phone</label>
                    <input id="phone" class="isp-form-input" type="text" name="phone" value="{{ old('phone') }}" />
                    @error('phone')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="role">Role</label>
                    <select id="role" name="role" class="isp-form-select" required>
                        <option value="isp_staff" @selected(old('role') === 'isp_staff')>Staff operator</option>
                        <option value="isp_technician" @selected(old('role') === 'isp_technician')>Field technician</option>
                        <option value="isp_admin" @selected(old('role') === 'isp_admin')>ISP administrator</option>
                    </select>
                    @error('role')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="password">Password</label>
                    <input id="password" class="isp-form-input" type="password" name="password" required autocomplete="new-password" />
                    @error('password')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" class="isp-form-input" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', true)) />
                <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Activate account immediately</span>
            </label>

            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-dashed border-[var(--pb-border)] pt-5">
                <a href="{{ route('isp.settings.users') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Create user</button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
