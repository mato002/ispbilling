@props([
    'action',
    'method' => 'POST',
])

@php
    $inFormModal = request()->header('Turbo-Frame') === 'pb-form-modal';
    $httpMethod = strtoupper($method);
@endphp

<form
    method="{{ $httpMethod === 'GET' ? 'GET' : 'POST' }}"
    action="{{ $action }}"
    @if (! $inFormModal) data-turbo-frame="pb-main" @endif
    {{ $attributes->merge(['class' => 'pb-form-shell space-y-4']) }}
>
    @csrf
    @if ($inFormModal)
        <input type="hidden" name="_pb_modal" value="1">
        <input type="hidden" name="_pb_modal_return" value="{{ url()->current() }}">
    @endif
    @if (! in_array($httpMethod, ['GET', 'POST'], true))
        @method($method)
    @endif
    @include('partials.modal-validation-alert')
    {{ $slot }}
</form>
