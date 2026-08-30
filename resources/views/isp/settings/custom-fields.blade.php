@extends('layouts.admin')

@section('title', 'Custom fields · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $rows = old('cf_key') !== null
            ? collect(old('cf_key', []))->keys()->map(fn ($i) => [
                'key' => old('cf_key.'.$i, ''),
                'label' => old('cf_label.'.$i, ''),
                'type' => old('cf_type.'.$i, 'text'),
                'required' => (bool) old('cf_required.'.$i, false),
                'rules' => old('cf_rules.'.$i, 'nullable|string|max:2000'),
                'options' => old('cf_options.'.$i, ''),
            ])->values()->all()
            : collect($definitions)->map(fn ($d) => [
                'key' => $d['key'],
                'label' => $d['label'],
                'type' => $d['type'],
                'required' => $d['required'],
                'rules' => $d['rules'],
                'options' => is_array($d['options'] ?? null) ? implode("\n", $d['options']) : '',
            ])->values()->all();
        if ($rows === []) {
            $rows = [['key' => '', 'label' => '', 'type' => 'text', 'required' => false, 'rules' => 'nullable|string|max:2000', 'options' => '']];
        }
    @endphp

    <x-isp-settings-workspace
        meta-title="Subscriber custom fields"
    >
        <form
            method="POST"
            action="{{ route('isp.settings.custom-fields.update') }}"
            class="isp-settings-form-deck"
            x-data='{ rows: @json($rows) }'
        >
            @csrf
            <div class="pb-card isp-settings-panel overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--pb-border)] px-4 py-3 sm:px-5">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Customer record extensions</h3>
                    <button type="button" class="pb-btn pb-btn-primary !py-1.5 !text-xs" @click="rows.push({ key: '', label: '', type: 'text', required: false, rules: 'nullable|string|max:2000', options: '' })">
                        Add field
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="px-3 py-2">Key</th>
                                <th class="px-3 py-2">Label</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Required</th>
                                <th class="px-3 py-2">Select options</th>
                                <th class="px-3 py-2">Extra rules (reference)</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--pb-border)]">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="align-top">
                                    <td class="px-3 py-2">
                                        <input type="text" class="isp-form-input font-mono text-xs" :name="'cf_key['+idx+']'" x-model="row.key" placeholder="national_id" pattern="[a-z0-9_]*" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" class="isp-form-input text-xs" :name="'cf_label['+idx+']'" x-model="row.label" placeholder="National ID" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <select class="isp-form-select text-xs" :name="'cf_type['+idx+']'" x-model="row.type">
                                            <option value="text">Text</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="number">Number</option>
                                            <option value="email">Email</option>
                                            <option value="date">Date</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <label class="flex items-center gap-2 text-xs">
                                            <input type="checkbox" :name="'cf_required['+idx+']'" value="1" :checked="row.required" @change="row.required = $el.checked" />
                                            Required
                                        </label>
                                    </td>
                                    <td class="px-3 py-2">
                                        <textarea class="isp-form-textarea text-xs" rows="2" :name="'cf_options['+idx+']'" x-model="row.options" placeholder="One option per line"></textarea>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" class="isp-form-input text-xs" :name="'cf_rules['+idx+']'" x-model="row.rules" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <button type="button" class="text-rose-600 hover:underline text-xs font-semibold" @click="rows.splice(idx,1); if(rows.length===0) rows.push({ key: '', label: '', type: 'text', required: false, rules: 'nullable|string|max:2000', options: '' })">Remove</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="isp-settings-sticky-save">
                <button type="submit" class="pb-btn pb-btn-primary">Save definitions</button>
                <a href="{{ route('isp.settings.index') }}" class="pb-btn">Back to hub</a>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
