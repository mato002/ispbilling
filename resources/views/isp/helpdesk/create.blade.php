@extends('layouts.admin')

@section('title', 'New helpdesk request')

@section('header')
    <x-isp-page-header
        title="Contact PrimeBilling support"
        module="Platform"
        description="Describe the issue (for example M-Pesa sync, router API, or billing). Our platform team will respond here."
    />
@endsection

@section('content')
    <div class="max-w-3xl">
        <x-form-section>
            <form method="POST" action="{{ route('isp.helpdesk.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="isp-form-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" class="isp-form-input" required maxlength="255" placeholder="e.g. M-Pesa payments not syncing">
                    @error('subject')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="isp-form-label">Details</label>
                    <textarea name="message" rows="8" class="isp-form-textarea" required placeholder="Steps to reproduce, error messages, time window…">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="isp-form-label">Priority</label>
                    <select name="priority" class="isp-form-select" required>
                        @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High — production blocked'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="pb-btn pb-btn-primary">Submit to PrimeBilling</button>
                    <a href="{{ route('isp.helpdesk.index') }}" class="pb-btn">Cancel</a>
                </div>
            </form>
        </x-form-section>
    </div>
@endsection
