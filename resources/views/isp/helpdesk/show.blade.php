@extends('layouts.admin')

@section('title', 'Helpdesk #'.$ticket->id)

@section('header')
    <x-isp-page-header
        :title="'Helpdesk #'.$ticket->id"
        module="Platform"
        :description="$ticket->subject"
    />
@endsection

@section('content')
    <div class="space-y-4">
        <a href="{{ route('isp.helpdesk.index') }}" class="pb-btn">Back to helpdesk</a>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-form-section title="Conversation">
                    <p class="mb-2 text-xs text-slate-500">Opened {{ $ticket->created_at->diffForHumans() }}</p>

                    <div class="mt-4 space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-sm font-semibold text-white">
                                {{ strtoupper(substr(optional($ticket->createdBy)->name ?? 'U', 0, 1)) }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                    {{ optional($ticket->createdBy)->name ?? 'User' }}
                                    <span class="text-xs font-normal text-slate-400">· {{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $ticket->message }}</p>
                            </div>
                        </div>

                        @foreach ($ticket->replies as $reply)
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-500 text-sm font-semibold text-white">
                                    {{ strtoupper(substr(optional($reply->user)->name ?? 'R', 0, 1)) }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ optional($reply->user)->name ?? 'Reply' }}
                                        <span class="text-xs font-normal text-slate-400">· {{ $reply->created_at->format('Y-m-d H:i') }}</span>
                                    </p>
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $reply->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-form-section>

                <x-form-section title="Add reply">
                    <form method="POST" action="{{ route('isp.helpdesk.reply', $ticket) }}" class="space-y-4">
                        @csrf
                        <textarea name="message" rows="4" class="isp-form-textarea" placeholder="Update or question for PrimeBilling…" required>{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button type="submit" class="pb-btn pb-btn-primary">Send reply</button>
                        </div>
                    </form>
                </x-form-section>
            </div>

            <div class="space-y-6">
                <x-form-section title="Ticket details">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">Status</dt>
                            <dd><x-status-badge :status="str_replace('_', ' ', $ticket->status)" /></dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">Priority</dt>
                            <dd class="capitalize text-slate-900 dark:text-slate-100">{{ $ticket->priority }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">Channel</dt>
                            <dd class="text-slate-900 dark:text-slate-100">PrimeBilling helpdesk</dd>
                        </div>
                    </dl>
                </x-form-section>

                <x-form-section title="Update ticket">
                    <form method="POST" action="{{ route('isp.helpdesk.update', $ticket) }}" class="space-y-4">
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

                        <div class="flex justify-end">
                            <button type="submit" class="pb-btn pb-btn-primary">Save</button>
                        </div>
                    </form>
                </x-form-section>
            </div>
        </div>
    </div>
@endsection
