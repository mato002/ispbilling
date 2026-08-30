@extends('layouts.admin')

@section('title', 'Edit user · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    <x-isp-settings-workspace
        meta-title="Edit workspace user"
>
        <form method="POST" action="{{ route('isp.settings.users.update', $user) }}" class="pb-card isp-settings-panel p-4 sm:p-6">
            @csrf
            @method('PUT')

            <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                <h3 class="isp-settings-section-title">{{ $user->name }}</h3>
</div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="name">Full name</label>
                    <input id="name" class="isp-form-input" type="text" name="name" value="{{ old('name', $user->name) }}" required />
                    @error('name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="email">Email</label>
                    <input id="email" class="isp-form-input" type="email" name="email" value="{{ old('email', $user->email) }}" required />
                    @error('email')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="phone">Phone</label>
                    <input id="phone" class="isp-form-input" type="text" name="phone" value="{{ old('phone', $user->phone) }}" />
                    @error('phone')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="role">Role</label>
                    <select id="role" name="role" class="isp-form-select" required>
                        <option value="isp_staff" @selected(old('role', $user->role) === 'isp_staff')>Staff operator</option>
                        <option value="isp_technician" @selected(old('role', $user->role) === 'isp_technician')>Field technician</option>
                        <option value="isp_admin" @selected(old('role', $user->role) === 'isp_admin')>ISP administrator</option>
                    </select>
                    @error('role')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="password">New password</label>
                    <input id="password" class="isp-form-input" type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current" />
                    @error('password')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" class="isp-form-input" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $user->is_active)) />
                <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Account is active</span>
            </label>
            @error('is_active')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror

            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-dashed border-[var(--pb-border)] pt-5">
                <a href="{{ route('isp.settings.users') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save changes</button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
