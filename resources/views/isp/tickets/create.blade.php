@extends('layouts.admin')

@section('title', 'New Support Ticket')

@section('header')
    <x-isp-page-header
        title="Create Ticket"
        module="Operations"
        description="Create a support issue for customer or internal follow-up."
    />
@endsection

@section('content')
    <div class="max-w-3xl">
        <x-form-section>
            <form method="POST" action="{{ route('isp.tickets.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="isp-form-label">Customer (optional)</label>
                    <select name="customer_id" class="isp-form-select">
                        <option value="">-- No specific customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                    @selected(old('customer_id', optional($preselectedCustomer)->id) == $customer->id)>
                                {{ $customer->full_name }} ({{ $customer->username }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="isp-form-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           class="isp-form-input">
                    @error('subject')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="isp-form-label">Priority</label>
                    <select name="priority" class="isp-form-select">
                        @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="isp-form-label">Message</label>
                    <textarea name="message" rows="6"
                              class="isp-form-textarea">{{ old('message') }}</textarea>
                    @error('message')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('isp.tickets.index') }}" class="pb-btn">Cancel</a>
                    <button type="submit" class="pb-btn pb-btn-primary">Create Ticket</button>
                </div>
            </form>
        </x-form-section>
    </div>
@endsection

