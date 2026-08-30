@extends('layouts.admin')

@section('title', 'Maintenance')

@section('content')
<div class="mx-auto max-w-lg py-16 text-center">
    <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Maintenance</p>
    <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $isp->company_name ?? $isp->name ?? 'Workspace' }}</h1>
    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ $message }}</p>
    @if(auth()->user()?->isIspAdmin())
        <a href="{{ route('isp.settings.maintenance') }}" class="mt-6 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Open maintenance settings
        </a>
    @endif
</div>
@endsection
