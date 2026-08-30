@props([
    'title',
    'cancelUrl' => null,
    'maxWidth' => '2xl',
])

@php
    $inFormModal = request()->header('Turbo-Frame') === 'pb-form-modal';
    $modalMaxWidth = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        default => 'sm:max-w-2xl',
    };
    $pageMaxWidth = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        default => 'max-w-2xl',
    };
@endphp

@if ($inFormModal)
    <div @class(['pb-form-modal mx-auto w-full', $modalMaxWidth]) data-pb-form-modal-panel>
        <div class="pb-form-modal__header">
            <h2 id="pb-form-modal-title" class="pb-form-modal__title">{{ $title }}</h2>
            <button
                type="button"
                class="pb-form-modal__close"
                data-pb-form-modal-close
                aria-label="{{ __('Close') }}"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="pb-form-modal__body">
            {{ $slot }}
        </div>
    </div>
@else
    <x-admin-layout :title="$title">
        @if ($cancelUrl)
            <x-slot name="header">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $title }}</h2>
                    <a href="{{ $cancelUrl }}" class="pb-btn pb-btn-secondary">{{ __('Back') }}</a>
                </div>
            </x-slot>
        @endif
        <div @class(['pb-card p-4 sm:p-6 mx-auto w-full', $pageMaxWidth])>
            {{ $slot }}
        </div>
    </x-admin-layout>
@endif
