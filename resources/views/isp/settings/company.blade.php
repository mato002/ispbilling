@extends('layouts.admin')

@section('title', 'Company · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $profile = $isp->settings['company_profile'] ?? [];
        $branding = $isp->settings['branding'] ?? [];
        $S = $ispSettingsShell;
        $initials = \Illuminate\Support\Str::of($isp->company_name ?? 'ISP')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))
            ->implode('');
        $initials = $initials !== '' ? strtoupper($initials) : 'ISP';
    @endphp

    <x-isp-settings-workspace
        meta-title="Company profile"
>
        @if ($S)
            <div class="space-y-3">
                @if ($S['mpesa']['tone'] === 'bad')
                    <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                        <span class="font-semibold">M-Pesa API not configured.</span>
                        <span class="text-amber-800/90 dark:text-amber-200/90"> Connect Daraja to collect STK and callbacks.</span>
                        <a href="{{ route('isp.settings.payment') }}" class="ml-1 font-semibold text-indigo-700 underline decoration-indigo-400/60 underline-offset-2 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200">Open M-Pesa</a>
                    </div>
                @endif
                @if ($S['smtp']['tone'] !== 'ok')
                    <div class="rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100">
                        <span class="font-semibold">SMTP credentials may be incomplete.</span>
                        <span class="text-slate-600 dark:text-slate-300"> Production mail should use a verified SMTP transport in your environment.</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="isp-settings-profile-hero pb-card isp-settings-panel isp-settings-panel--lift overflow-hidden">
            <div class="relative border-b border-[var(--pb-border)] bg-gradient-to-br from-indigo-500/[0.12] via-transparent to-cyan-500/10 px-4 py-5 sm:px-6">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(99,102,241,0.14),transparent_55%)] dark:bg-[radial-gradient(ellipse_at_top_right,rgba(99,102,241,0.22),transparent_50%)]"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        @if (! empty($isp->logo))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($isp->logo) }}" alt="" class="h-16 w-16 shrink-0 rounded-2xl border border-[var(--pb-border)] object-cover shadow-md ring-2 ring-white/80 dark:ring-slate-800/80" />
                        @else
                            <div class="isp-settings-profile-avatar h-16 w-16 shrink-0 text-lg">{{ $initials }}</div>
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-lg font-bold text-slate-900 dark:text-white">{{ $isp->company_name ?: 'Company name' }}</h2>
                                @if ($isp->is_active)
                                    <span class="pb-pill pb-pill-ok text-[10px]">Company active</span>
                                @else
                                    <span class="pb-pill pb-pill-bad text-[10px]">Suspended</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                ISP ID <span class="font-mono font-semibold text-slate-700 dark:text-slate-200">{{ $S ? 'ISP-'.str_pad((string) $isp->id, 6, '0', STR_PAD_LEFT) : $isp->id }}</span>
                                <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
                                {{ $profile['business_type'] ?? 'ISP / connectivity' }}
                            </p>
                            @if ($S)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="pb-pill pb-pill-info text-[10px]">Active customers (est.) {{ $S['stats']['active_customers'] }}</span>
                                    <span class="pb-pill pb-pill-cyan text-[10px]">Onboarding {{ $S['onboarding_percent'] }}%</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('isp.settings.company.update') }}"
            enctype="multipart/form-data"
            class="isp-settings-form-deck"
            x-data="{ dirty: false, saving: false }"
            @input="dirty = true"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div id="branding" class="pb-card isp-settings-panel p-4 sm:p-6 shadow-[0_1px_0_rgba(255,255,255,0.55)_inset] dark:shadow-[0_1px_0_rgba(255,255,255,0.04)_inset]">
                <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">Branding &amp; workspace</h3>
</div>

                <div class="mb-6 flex flex-wrap gap-4 rounded-xl border border-[var(--pb-border)] bg-slate-50/50 p-4 dark:bg-slate-900/30">
                    <div class="text-center">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Admin logo</p>
                        @if (! empty($isp->logo))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($isp->logo) }}" alt="" class="mt-2 h-14 w-14 rounded-xl border border-[var(--pb-border)] object-cover" />
                        @else
                            <div class="mt-2 flex h-14 w-14 items-center justify-center rounded-xl border border-dashed border-slate-300 text-xs text-slate-400">None</div>
                        @endif
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Favicon</p>
                        @if (! empty($branding['favicon_path']))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['favicon_path']) }}" alt="" class="mt-2 h-14 w-14 rounded-xl border border-[var(--pb-border)] object-contain p-1" />
                        @else
                            <div class="mt-2 flex h-14 w-14 items-center justify-center rounded-xl border border-dashed border-slate-300 text-xs text-slate-400">None</div>
                        @endif
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Portal logo</p>
                        @if (! empty($branding['portal_logo_path']))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['portal_logo_path']) }}" alt="" class="mt-2 h-14 w-14 rounded-xl border border-[var(--pb-border)] object-cover" />
                        @elseif (! empty($isp->logo))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($isp->logo) }}" alt="" class="mt-2 h-14 w-14 rounded-xl border border-[var(--pb-border)] object-cover opacity-70" title="Falls back to company logo" />
                        @else
                            <div class="mt-2 flex h-14 w-14 items-center justify-center rounded-xl border border-dashed border-slate-300 text-xs text-slate-400">None</div>
                        @endif
                    </div>
                    <div class="min-w-[8rem] flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Preview</p>
                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">{{ $branding['workspace_title'] ?? config('app.name') }}</p>
                        <p class="text-xs text-slate-500">{{ $branding['workspace_tagline'] ?? $isp->name }}</p>
                        <span class="mt-2 inline-block h-3 w-12 rounded-full" style="background: {{ $branding['accent_hex'] ?? '#6366f1' }}"></span>
                    </div>
                </div>

                <p class="mb-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Admin console</p>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="logo">Company logo</label>
                        <input id="logo" class="isp-form-input" type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PNG or WebP, max 4&nbsp;MB.</p>
@if (! empty($isp->logo))
                            <label class="mt-3 flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300" @checked(old('remove_logo')) />
                                Remove current logo
                            </label>
                        @endif
                    </div>
                    <div>
                        <label class="isp-form-label" for="favicon">Favicon</label>
                        <input id="favicon" class="isp-form-input" type="file" name="favicon" accept=".ico,image/png,image/svg+xml,image/jpeg" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">ICO or PNG, max 1&nbsp;MB.</p>
@if (! empty($branding['favicon_path']))
                            <label class="mt-3 flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                <input type="checkbox" name="remove_favicon" value="1" class="rounded border-slate-300" @checked(old('remove_favicon')) />
                                Remove current favicon
                            </label>
                        @endif
                    </div>
                    <div>
                        <label class="isp-form-label" for="workspace_title">Workspace title</label>
                        <input id="workspace_title" class="isp-form-input" type="text" name="workspace_title" value="{{ old('workspace_title', $branding['workspace_title'] ?? config('app.name')) }}" placeholder="PrimeBilling" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="workspace_tagline">Workspace tagline</label>
                        <input id="workspace_tagline" class="isp-form-input" type="text" name="workspace_tagline" value="{{ old('workspace_tagline', $branding['workspace_tagline'] ?? $isp->name) }}" placeholder="Short line under title" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="accent_hex">Accent color</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input id="accent_hex" class="isp-form-input max-w-[11rem] font-mono" type="text" name="accent_hex" value="{{ old('accent_hex', $branding['accent_hex'] ?? '#6366f1') }}" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#6366f1" />
                            <input type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-[var(--pb-border)] bg-[var(--pb-panel)] p-1" value="{{ old('accent_hex', $branding['accent_hex'] ?? '#6366f1') }}" oninput="document.getElementById('accent_hex').value = this.value" aria-label="Pick accent color" />
                        </div>
</div>
                </div>

                <div class="mt-8 border-t border-dashed border-[var(--pb-border)] pt-6">
                    <p class="mb-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Customer portal &amp; invoices</p>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="portal_logo">Portal logo</label>
                            <input id="portal_logo" class="isp-form-input" type="file" name="portal_logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" />
@if (! empty($branding['portal_logo_path']))
                                <label class="mt-3 flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                    <input type="checkbox" name="remove_portal_logo" value="1" class="rounded border-slate-300" @checked(old('remove_portal_logo')) />
                                    Remove portal logo
                                </label>
                            @endif
                        </div>
                        <div>
                            <label class="isp-form-label" for="portal_title">Portal title</label>
                            <input id="portal_title" class="isp-form-input" type="text" name="portal_title" value="{{ old('portal_title', $branding['portal_title'] ?? $isp->company_name) }}" placeholder="Customer-facing brand name" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="isp-form-label" for="portal_welcome_message">Portal welcome message</label>
                            <textarea id="portal_welcome_message" class="isp-form-textarea" name="portal_welcome_message" rows="2" placeholder="Short greeting on login or splash screens">{{ old('portal_welcome_message', $branding['portal_welcome_message'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="isp-form-label" for="portal_support_email">Portal support email</label>
                            <input id="portal_support_email" class="isp-form-input" type="email" name="portal_support_email" value="{{ old('portal_support_email', $branding['portal_support_email'] ?? $isp->email) }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="invoice_footer">Invoice footer text</label>
                            <input id="invoice_footer" class="isp-form-input" type="text" name="invoice_footer" value="{{ old('invoice_footer', $branding['invoice_footer'] ?? '') }}" placeholder="Thank you for your business." />
                        </div>
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6 shadow-[0_1px_0_rgba(255,255,255,0.55)_inset] dark:shadow-[0_1px_0_rgba(255,255,255,0.04)_inset]">
                <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">Identity &amp; contact</h3>
</div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="company_name">Company name</label>
                        <input id="company_name" class="isp-form-input" type="text" name="company_name" value="{{ old('company_name', $isp->company_name) }}" required />
                    </div>
                    <div>
                        <label class="isp-form-label" for="email">Email</label>
                        <input id="email" class="isp-form-input" type="email" name="email" value="{{ old('email', $isp->email) }}" required />
</div>
                    <div>
                        <label class="isp-form-label" for="phone">Phone</label>
                        <input id="phone" class="isp-form-input" type="text" name="phone" value="{{ old('phone', $isp->phone) }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="address">Address</label>
                        <textarea id="address" class="isp-form-textarea" name="address" rows="3">{{ old('address', $isp->address) }}</textarea>
                    </div>
                    <div>
                        <label class="isp-form-label" for="website">Website</label>
                        <input id="website" class="isp-form-input" type="text" name="website" value="{{ old('website', $profile['website'] ?? '') }}" placeholder="https://example.co.ke" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="business_type">Business type</label>
                        <select id="business_type" class="isp-form-select" name="business_type">
                            @php $bt = old('business_type', $profile['business_type'] ?? 'isp'); @endphp
                            <option value="isp" @selected($bt === 'isp')>ISP / connectivity</option>
                            <option value="wisp" @selected($bt === 'wisp')>WISP / fixed wireless</option>
                            <option value="fiber" @selected($bt === 'fiber')>Fiber retail</option>
                            <option value="hotspot" @selected($bt === 'hotspot')>Hotspot operator</option>
                            <option value="other" @selected($bt === 'other')>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="isp-form-label" for="tax_pin">Tax PIN</label>
                        <input id="tax_pin" class="isp-form-input" type="text" name="tax_pin" value="{{ old('tax_pin', $profile['tax_pin'] ?? '') }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="registration_number">Registration number</label>
                        <input id="registration_number" class="isp-form-input" type="text" name="registration_number" value="{{ old('registration_number', $profile['registration_number'] ?? '') }}" />
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6 shadow-[0_1px_0_rgba(255,255,255,0.55)_inset] dark:shadow-[0_1px_0_rgba(255,255,255,0.04)_inset]">
                <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">Regional operations</h3>
</div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="currency">Currency</label>
                        <select id="currency" class="isp-form-select" name="currency">
                            <option value="KES" @selected(old('currency', $isp->currency ?? 'KES') == 'KES')>KES — Kenyan Shilling</option>
                            <option value="USD" @selected(old('currency', $isp->currency ?? '') == 'USD')>USD — US Dollar</option>
                            <option value="TZS" @selected(old('currency', $isp->currency ?? '') == 'TZS')>TZS — Tanzanian Shilling</option>
                            <option value="UGX" @selected(old('currency', $isp->currency ?? '') == 'UGX')>UGX — Ugandan Shilling</option>
                        </select>
                    </div>
                    <div>
                        <label class="isp-form-label" for="timezone">Timezone</label>
                        <select id="timezone" class="isp-form-select" name="timezone">
                            <option value="Africa/Nairobi" @selected(old('timezone', $isp->timezone ?? 'Africa/Nairobi') == 'Africa/Nairobi')>Africa/Nairobi</option>
                            <option value="Africa/Dar_es_Salaam" @selected(old('timezone', $isp->timezone ?? '') == 'Africa/Dar_es_Salaam')>Africa/Dar es Salaam</option>
                            <option value="Africa/Kampala" @selected(old('timezone', $isp->timezone ?? '') == 'Africa/Kampala')>Africa/Kampala</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="isp-settings-sticky-save">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save company profile</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
