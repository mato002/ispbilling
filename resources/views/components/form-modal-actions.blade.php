@props([
    'cancelUrl' => null,
])

@php
    $inFormModal = request()->header('Turbo-Frame') === 'pb-form-modal';
@endphp

<div class="pb-form-modal__actions flex flex-wrap items-center justify-end gap-3 border-t border-[var(--pb-border)] pt-4 mt-6">
    @if ($inFormModal)
        <button type="button" class="pb-btn pb-btn-secondary" data-pb-form-modal-close>
            {{ __('Cancel') }}
        </button>
    @elseif ($cancelUrl)
        <a href="{{ $cancelUrl }}" class="pb-btn pb-btn-secondary">{{ __('Cancel') }}</a>
    @endif
    {{ $slot }}
</div>
