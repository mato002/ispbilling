@extends('customer.layout')

@section('title', 'Usage')

@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Data usage</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 mb-4">Usage by day (last 30 days).</p>
        @if($usageStats->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Download</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Upload</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sessions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($usageStats as $stat)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $stat->date->format('M d, Y') }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-800">{{ $stat->formatted_downloaded ?? number_format($stat->bytes_downloaded / 1024 / 1024, 2) . ' MB' }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-800">{{ $stat->formatted_uploaded ?? number_format($stat->bytes_uploaded / 1024 / 1024, 2) . ' MB' }}</td>
                                <td class="px-4 py-2 text-sm text-right font-medium text-gray-800">{{ $stat->formatted_total_bytes ?? number_format($stat->total_bytes / 1024 / 1024, 2) . ' MB' }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-800">{{ $stat->session_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $usageStats->links() }}
            </div>
        @else
            <p class="text-gray-500">No usage data yet.</p>
        @endif
    </div>
</div>
@endsection
