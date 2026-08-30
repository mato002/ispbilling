@if (session('modal_error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" role="alert" data-pb-validation-errors data-pb-modal-error>
        <p class="font-medium">{{ __('Unable to save. Please review the form and try again.') }}</p>
        <p class="mt-2">{{ session('modal_error') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" role="alert" data-pb-validation-errors>
        <p class="font-medium">{{ $title ?? __('Please correct the highlighted fields.') }}</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
