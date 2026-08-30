@props([
    'cancelRoute' => 'isp.settings.index',
    'saveLabel' => 'Save changes',
])

<div class="flex flex-wrap items-center justify-end gap-2 pt-2">
    <a href="{{ route($cancelRoute) }}" class="pb-btn" data-turbo-frame="pb-main">Cancel</a>
    <button type="submit" class="pb-btn pb-btn-primary" :disabled="saving" x-text="saving ? 'Saving…' : '{{ $saveLabel }}'"></button>
</div>
