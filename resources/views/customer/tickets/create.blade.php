@extends('customer.layout')

@section('title', 'New ticket')

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <a href="{{ route('customer.tickets.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to tickets</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Open a support ticket</h2>
        <p class="mt-1 text-sm text-slate-500">Describe the issue and we will get back to you.</p>
    </div>

    <form method="POST" action="{{ route('customer.tickets.store') }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="subject" class="mb-1.5 block text-sm font-semibold text-slate-700">Subject</label>
            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                   placeholder="e.g. Slow speeds since yesterday">
            @error('subject')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="priority" class="mb-1.5 block text-sm font-semibold text-slate-700">Priority</label>
            <select id="priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="low" @selected(old('priority') === 'low')>Low</option>
                <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                <option value="high" @selected(old('priority') === 'high')>High</option>
            </select>
            @error('priority')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="message" class="mb-1.5 block text-sm font-semibold text-slate-700">Message</label>
            <textarea id="message" name="message" rows="6" required maxlength="5000"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                      placeholder="Include account details, when it started, and anything you already tried.">{{ old('message') }}</textarea>
            @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Submit ticket
            </button>
        </div>
    </form>
</div>
@endsection
