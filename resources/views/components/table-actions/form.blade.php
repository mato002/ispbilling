@props([
    'action',
    'method' => 'POST',
    'label',
    'danger' => true,
    'onsubmit' => null,
    'turboFrame' => 'pb-main',
])

<form
    method="POST"
    action="{{ $action }}"
    data-turbo-frame="{{ $turboFrame }}"
    @if($onsubmit) onsubmit="{{ $onsubmit }}" @endif
    {{ $attributes->merge(['class' => '']) }}
>
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button
        type="submit"
        class="block w-full px-3 py-2 text-left text-sm {{ $danger ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800' }}"
    >
        {{ $label }}
    </button>
</form>
