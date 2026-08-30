@extends('layouts.admin')

@section('title', 'Ticket #' . $ticket->id)

@section('header')
    <x-isp-page-header
        :title="'Ticket #'.$ticket->id"
        module="Operations"
        :description="$ticket->subject"
    />
@endsection

@section('content')
    <div class="space-y-4">
        <a href="{{ route('isp.tickets.index') }}" class="pb-btn">Back to Tickets</a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-form-section title="Conversation">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-slate-400">Created {{ $ticket->created_at->diffForHumans() }}</span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4 space-y-4">
                        <div class="flex items-start space-x-3">
                            @php
                                $openerName = optional($ticket->createdBy)->name
                                    ?? optional($ticket->customer)->full_name
                                    ?? 'System';
                            @endphp
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-500 text-white text-sm font-semibold">
                                    {{ strtoupper(substr($openerName, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $openerName }}
                                    </p>
                                    <span class="text-xs text-gray-400">
                                        opened the ticket • {{ $ticket->created_at->format('Y-m-d H:i') }}
                                        @if(! $ticket->created_by_user_id && $ticket->customer_id)
                                            · customer portal
                                        @endif
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
                </x-form-section>

                <x-form-section title="Add Reply">
                    <form method="POST" action="{{ route('isp.tickets.reply', $ticket) }}" class="space-y-4">
                        @csrf
                        <div>
                            <textarea name="message" rows="4"
                                      class="isp-form-textarea"
                                      placeholder="Write your reply here...">{{ old('message') }}</textarea>
                            @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="pb-btn pb-btn-primary">Send Reply</button>
                        </div>
                    </form>
                </x-form-section>
            </div>

            <div class="space-y-6">
                <x-form-section title="Ticket Details">

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <x-status-badge :status="str_replace('_', ' ', $ticket->status)" />
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
                </x-form-section>

                <x-form-section title="Update Ticket">
                    <form method="POST" action="{{ route('isp.tickets.update', $ticket) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="isp-form-label">Status</label>
                            <select name="status" class="isp-form-select">
                                @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="isp-form-label">Priority</label>
                            <select name="priority" class="isp-form-select">
                                @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="isp-form-label">Assigned to</label>
                            <select name="assigned_to_user_id" class="isp-form-select">
                                <option value="">Unassigned</option>
                                @foreach(($staffUsers ?? []) as $staff)
                                    <option value="{{ $staff->id }}" @selected((string) old('assigned_to_user_id', $ticket->assigned_to_user_id) === (string) $staff->id)>
                                        {{ $staff->name }} ({{ $staff->role === 'isp_admin' ? 'Admin' : 'Staff' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to_user_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="pb-btn pb-btn-primary">Save Changes</button>
                        </div>
                    </form>
                </x-form-section>
            </div>
        </div>
    </div>
@endsection

