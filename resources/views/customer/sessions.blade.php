@extends('customer.layout')

@section('title', 'Sessions')

@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Session history</h2>

    <div class="bg-white rounded-lg shadow p-6">
        @if($sessions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ended</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Data used</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sessions as $s)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $s->started_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $s->ended_at?->format('M d, Y H:i') ?? ($s->status === 'active' ? 'Active' : '—') }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $s->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ $s->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-right text-gray-800">
                                    @if($s->total_bytes)
                                        {{ number_format($s->total_bytes / 1024 / 1024, 2) }} MB
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $sessions->links() }}
            </div>
        @else
            <p class="text-gray-500">No sessions yet.</p>
        @endif
    </div>
</div>
@endsection
