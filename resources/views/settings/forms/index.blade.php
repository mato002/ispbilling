@extends('layouts.admin')

@section('title', $activeForm ? $activeForm['label'].' · Form controls' : 'Form controls')

@section('header')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Settings</p>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $activeForm ? $activeForm['label'] : __('Forms control center') }}
            </h2>
            @if ($activeForm)
@else
@endif
        </div>
        <a href="{{ $activeForm ? route($scope === 'platform' ? 'admin.settings.forms.index' : 'isp.settings.forms.index') : $settingsBackUrl }}" data-turbo-frame="pb-main" data-turbo-action="advance" class="pb-btn pb-btn-secondary">
            {{ $activeForm ? __('All forms') : __('Back to settings') }}
        </a>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($activeForm)
        @include('settings.forms.partials.workspace', [
            'form' => $activeForm,
            'updateRoute' => $updateRoute,
        ])
    @else
        @include('settings.forms.partials.landing', [
            'forms' => $forms,
            'scope' => $scope,
        ])
    @endif
@endsection
