@php
    $ann = $announcement ?? null;
    $action = $ann ? route('admin.announcements.update', $ann) : route('admin.announcements.store');
    $bare = $bare ?? false;
@endphp

@if ($bare)
    <div class="space-y-5">
@else
<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($ann)
        @method('PUT')
    @endif
@endif

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Title</label>
        <input type="text" name="title" value="{{ old('title', $ann?->title ?? '') }}" required maxlength="255"
               class="mt-1 w-full max-w-xl rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
        @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Message</label>
        <textarea name="body" rows="6" required class="mt-1 w-full max-w-2xl rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">{{ old('body', $ann?->body ?? '') }}</textarea>
        @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-slate-500">Shown on every ISP dashboard while active and within the schedule.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Severity</label>
        <select name="severity" class="mt-1 w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
            @foreach (['info' => 'Info', 'warning' => 'Warning', 'critical' => 'Critical'] as $val => $lab)
                <option value="{{ $val }}" @selected(old('severity', $ann?->severity ?? 'info') === $val)>{{ $lab }}</option>
            @endforeach
        </select>
        @error('severity')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Show from (optional)</label>
            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $ann?->starts_at ? $ann->starts_at->format('Y-m-d\TH:i') : '') }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
            @error('starts_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Hide after (optional)</label>
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $ann?->ends_at ? $ann->ends_at->format('Y-m-d\TH:i') : '') }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
            @error('ends_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded border-slate-300"
               @checked(old('is_active', $ann?->is_active ?? true))>
        <label for="is_active" class="text-sm text-slate-700 dark:text-slate-300">Active (visible to ISPs when in window)</label>
    </div>

    @if (! $bare)
    <div class="flex flex-wrap gap-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $ann ? 'Save changes' : 'Publish' }}</button>
        <a href="{{ route('admin.announcements.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancel</a>
    </div>
    </form>
    @else
    </div>
    @endif
