@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Ticket #{{ $ticket->id }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $ticket->subject }}</p>
            </div>
            <a href="{{ route('isp.tickets.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Back to Tickets
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-medium text-gray-900">Conversation</h2>
                        <span class="text-xs text-gray-400">Created {{ $ticket->created_at->diffForHumans() }}</span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4 space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-500 text-white text-sm font-semibold">
                                    {{ strtoupper(substr(optional($ticket->createdBy)->name ?? 'S', 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ optional($ticket->createdBy)->name ?? 'System' }}
                                    </p>
                                    <span class="text-xs text-gray-400">
                                        opened the ticket • {{ $ticket->created_at->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                                    {{ $ticket->message }}
                                </p>
                            </div>
                        </div>

                        @foreach($ticket->replies as $reply)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-500 text-white text-sm font-semibold">
                                        {{ strtoupper(substr(optional($reply->user)->name ?? 'C', 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ optional($reply->user)->name ?? optional($reply->customer)->full_name ?? 'Customer' }}
                                        </p>
                                        <span class="text-xs text-gray-400">
                                            replied • {{ $reply->created_at->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                                        {{ $reply->message }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Reply</h2>
                    <form method="POST" action="{{ route('isp.tickets.reply', $ticket) }}" class="space-y-4">
                        @csrf
                        <div>
                            <textarea name="message" rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                      placeholder="Write your reply here...">{{ old('message') }}</textarea>
                            @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Ticket Details</h2>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                @php
                                    $statusColors = [
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'resolved' => 'bg-green-100 text-green-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </dd>
                        </div>

                        <div class="flex justify-between">
                            <dt class="text-gray-500">Priority</dt>
                            <dd class="text-gray-900 capitalize">{{ str_replace('_', ' ', $ticket->priority) }}</dd>
                        </div>

                        <div class="flex justify-between">
                            <dt class="text-gray-500">Customer</dt>
                            <dd class="text-gray-900">
                                @if($ticket->customer)
                                    {{ $ticket->customer->full_name }} ({{ $ticket->customer->username }})
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="flex justify-between">
                            <dt class="text-gray-500">Assigned To</dt>
                            <dd class="text-gray-900">
                                {{ optional($ticket->assignedTo)->name ?? '-' }}
                            </dd>
                        </div>

                        <div class="flex justify-between">
                            <dt class="text-gray-500">Channel</dt>
                            <dd class="text-gray-900 capitalize">{{ $ticket->channel }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Update Ticket</h2>
                    <form method="POST" action="{{ route('isp.tickets.update', $ticket) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select name="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                            <select name="priority"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assigned To (User ID)</label>
                            <input type="number" name="assigned_to_user_id" value="{{ old('assigned_to_user_id', $ticket->assigned_to_user_id) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('assigned_to_user_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-800 hover:bg-gray-900">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

