<div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($forms as $form)
        <a
            href="{{ route($scope === 'platform' ? 'admin.settings.forms.index' : 'isp.settings.forms.index', ['form' => $form['form_key']]) }}"
            data-turbo-frame="pb-main"
            data-turbo-action="advance"
            class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5"
        >
            <span class="isp-cc-module__accent isp-cc-module__accent--indigo"></span>
            <div class="relative">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">{{ count($form['fields']) }} fields</p>
                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">{{ $form['label'] }}</h4>
<div class="mt-3 flex flex-wrap gap-2">
                    <span @class([
                        'pb-pill text-[10px]',
                        'pb-pill-ok' => $form['is_active'],
                        'pb-pill-bad' => ! $form['is_active'],
                    ])>{{ $form['is_active'] ? __('Active') : __('Inactive') }}</span>
                </div>
            </div>
        </a>
    @endforeach
</div>
