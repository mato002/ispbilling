@extends('customer.layout')

@section('title', 'Ticket #'.$ticket->id)

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('customer.tickets.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to tickets</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $ticket->subject }}</h2>
                <p class="mt-1 text-sm text-slate-500">Ticket #{{ $ticket->id }} · opened {{ $ticket->created_at?->diffForHumans() }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                {{ str_replace('_', ' ', $ticket->status) }}
            </span>
        </div>
    </div>

    <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-100 pb-4">
            <p class="text-sm font-semibold text-slate-900">{{ $customer->full_name }} <span class="font-normal text-slate-400">· you</span></p>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $ticket->message }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $ticket->created_at?->format('M d, Y H:i') }}</p>
        </div>

        @foreach($ticket->replies as $reply)
            @php
                $fromStaff = filled($reply->user_id);
                $name = $fromStaff
                    ? ($reply->user?->name ?? 'Support')
                    : ($reply->customer?->full_name ?? 'You');
            @endphp
            <div class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                <p class="text-sm font-semibold text-slate-900">
                    {{ $name }}
                    <span class="font-normal text-slate-400">· {{ $fromStaff ? 'support' : 'you' }}</span>
                </p>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $reply->message }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $reply->created_at?->format('M d, Y H:i') }}</p>
            </div>
        @endforeach
    </div>

    @if(! in_array($ticket->status, ['closed', 'resolved'], true))
        <form method="POST" action="{{ route('customer.tickets.reply', $ticket) }}"
              class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="message" class="mb-1.5 block text-sm font-semibold text-slate-700">Add a reply</label>
                <textarea id="message" name="message" rows="4" required maxlength="5000"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                          placeholder="Add more details or answer our questions…">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Send reply
                </button>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            This ticket is {{ str_replace('_', ' ', $ticket->status) }}.
            <a href="{{ route('customer.tickets.create') }}" class="font-semibold text-indigo-600 hover:underline">Open a new ticket</a>
            if you still need help.
        </div>
    @endif
</div>
@endsection
