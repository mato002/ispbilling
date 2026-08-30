@extends('customer.layout')

@section('title', 'Support')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-900">Support tickets</h2>
        <a href="{{ route('customer.tickets.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            New ticket
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if($tickets->count())
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tickets as $ticket)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $ticket->id }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('customer.tickets.show', $ticket) }}" class="font-medium text-indigo-600 hover:underline">
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium uppercase text-slate-700">
                                    {{ str_replace('_', ' ', $ticket->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $ticket->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $tickets->links() }}
            </div>
        @else
            <div class="px-6 py-10 text-center">
                <p class="text-slate-500">You have not opened any tickets yet.</p>
                <a href="{{ route('customer.tickets.create') }}" class="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:underline">
                    Open your first ticket
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
