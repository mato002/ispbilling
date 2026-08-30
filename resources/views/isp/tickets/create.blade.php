@php($fields = $formFields ?? [])

<x-modal-form :title="__('Create Ticket')" :cancel-url="route('isp.tickets.index')" maxWidth="3xl">
    <x-form-shell :action="route('isp.tickets.store')">
        <x-form-controlled :fields="$fields" name="customer_id">
            <div>
                <label class="isp-form-label">Customer (optional)</label>
                <select name="customer_id" class="isp-form-select" @disabled($fields['customer_id']['read_only'] ?? false)>
                    <option value="">-- No specific customer --</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', optional($preselectedCustomer)->id) == $customer->id)>
                            {{ $customer->full_name }} ({{ $customer->username }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </x-form-controlled>

        <x-form-controlled :fields="$fields" name="subject">
            <div>
                <label class="isp-form-label">Subject @if($fields['subject']['required'] ?? true)*@endif</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="isp-form-input"
                       @required($fields['subject']['required'] ?? true)
                       @readonly($fields['subject']['read_only'] ?? false)>
                @error('subject')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </x-form-controlled>

        <x-form-controlled :fields="$fields" name="priority">
            <div>
                <label class="isp-form-label">Priority</label>
                <select name="priority" class="isp-form-select" @required($fields['priority']['required'] ?? true) @disabled($fields['priority']['read_only'] ?? false)>
                    @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </x-form-controlled>

        <x-form-controlled :fields="$fields" name="message">
            <div>
                <label class="isp-form-label">Message @if($fields['message']['required'] ?? true)*@endif</label>
                <textarea name="message" rows="6" class="isp-form-textarea"
                          @required($fields['message']['required'] ?? true)
                          @readonly($fields['message']['read_only'] ?? false)>{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </x-form-controlled>

        <x-form-modal-actions :cancel-url="route('isp.tickets.index')">
            <button type="submit" class="pb-btn pb-btn-primary">{{ __('Create ticket') }}</button>
        </x-form-modal-actions>
    </x-form-shell>
</x-modal-form>
