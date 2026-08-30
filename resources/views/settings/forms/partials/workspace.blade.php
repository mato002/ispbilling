<form method="POST" action="{{ $updateRoute }}" class="isp-settings-form-deck">
    @csrf
    <input type="hidden" name="return_form" value="{{ $form['form_key'] }}">

    <div class="pb-card isp-settings-panel overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--pb-border)] px-4 py-3 sm:px-5">
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $form['label'] }}</p>
</div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="forms[{{ $form['form_key'] }}][is_active]" value="0">
                <input
                    type="checkbox"
                    name="forms[{{ $form['form_key'] }}][is_active]"
                    value="1"
                    class="rounded border-[var(--pb-border)] text-indigo-600 focus:ring-indigo-500"
                    @checked($form['is_active'])
                >
                <span class="font-medium text-slate-600 dark:text-slate-300">{{ __('Form active') }}</span>
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--pb-border)] text-sm">
                <thead class="bg-slate-50/80 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300 sm:px-5">{{ __('Field') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Visibility') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Requirement') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Read only') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Default') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--pb-border)] bg-white dark:bg-transparent">
                    @foreach ($form['fields'] as $field)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3 sm:px-5">
                                <span class="font-medium text-slate-800 dark:text-slate-100">{{ $field['label'] }}</span>
                                <span class="block font-mono text-xs text-slate-400">{{ $field['field_key'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $visibilityLocked = ($field['registry_required'] ?? false) || ($field['required'] && ! ($field['registry_required'] ?? false));
                                    $visibilityValue = $field['hidden'] ? 'hidden' : 'visible';
                                @endphp
                                @if ($visibilityLocked)
                                    <input type="hidden" name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][visibility]" value="{{ $visibilityValue }}">
                                @endif
                                <select
                                    name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][visibility]"
                                    class="isp-form-select min-w-[7rem]"
                                    @disabled($visibilityLocked)
                                >
                                    <option value="visible" @selected($field['visible'])>{{ __('Visible') }}</option>
                                    <option value="hidden" @selected($field['hidden'])>{{ __('Hidden') }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                @if ($field['registry_required'] ?? false)
                                    <input type="hidden" name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][requirement]" value="required">
                                @endif
                                <select
                                    name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][requirement]"
                                    class="isp-form-select min-w-[7rem]"
                                    @disabled($field['registry_required'] ?? false)
                                >
                                    <option value="required" @selected($field['required'])>{{ __('Required') }}</option>
                                    <option value="optional" @selected(! $field['required'])>{{ __('Optional') }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="hidden" name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][read_only]" value="0">
                                <input
                                    type="checkbox"
                                    name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][read_only]"
                                    value="1"
                                    class="rounded border-[var(--pb-border)] text-indigo-600 focus:ring-indigo-500"
                                    @checked($field['read_only'] ?? false)
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    name="forms[{{ $form['form_key'] }}][fields][{{ $field['field_key'] }}][default_value]"
                                    value="{{ $field['default'] ?? '' }}"
                                    class="isp-form-input min-w-[8rem]"
                                    placeholder="—"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="pb-btn pb-btn-primary">{{ __('Save form settings') }}</button>
    </div>
</form>
