@props([
    'definitions' => [],
    'values' => [],
])

@if (count($definitions))
    <div class="pb-card isp-settings-panel border border-indigo-200/40 p-4 dark:border-indigo-500/20 sm:p-6">
        <div class="mb-4 border-b border-dashed border-[var(--pb-border)] pb-4">
            <h3 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">Extended attributes</h3>
</div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($definitions as $def)
                @php
                    $name = 'custom_fields['.$def['key'].']';
                    $val = old('custom_fields.'.$def['key'], $values[$def['key']] ?? '');
                @endphp
                <div class="{{ ($def['type'] ?? 'text') === 'textarea' ? 'md:col-span-2' : '' }}">
                    <label class="isp-form-label" for="cf_{{ $def['key'] }}">{{ $def['label'] }}@if (! empty($def['required'])) <span class="text-rose-500">*</span> @endif</label>
                    @if (($def['type'] ?? 'text') === 'textarea')
                        <textarea id="cf_{{ $def['key'] }}" class="isp-form-textarea" name="{{ $name }}" rows="3" @if (! empty($def['required'])) required @endif>{{ is_scalar($val) ? $val : '' }}</textarea>
                    @elseif (($def['type'] ?? 'text') === 'select')
                        <select id="cf_{{ $def['key'] }}" class="isp-form-select" name="{{ $name }}" @if (! empty($def['required'])) required @endif>
                            <option value="">—</option>
                            @foreach ($def['options'] ?? [] as $opt)
                                <option value="{{ $opt }}" @selected((string) $val === (string) $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            id="cf_{{ $def['key'] }}"
                            class="isp-form-input"
                            name="{{ $name }}"
                            type="{{ $def['type'] === 'email' ? 'email' : ($def['type'] === 'date' ? 'date' : ($def['type'] === 'number' ? 'number' : 'text')) }}"
                            value="{{ is_scalar($val) ? $val : '' }}"
                            @if (! empty($def['required'])) required @endif
                        />
                    @endif
                    @if (! empty($def['rules']) && ($def['rules'] ?? '') !== 'nullable|string|max:2000')
@endif
                </div>
            @endforeach
        </div>
    </div>
@endif
