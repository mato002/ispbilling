<x-modal-form :title="__('Create Plan')" :cancel-url="route('isp.plans.index')" maxWidth="4xl">
    <x-form-shell :action="route('isp.plans.store')">
        @include('isp.plans.partials.form-fields')
        <x-form-modal-actions :cancel-url="route('isp.plans.index')">
            <button type="submit" class="pb-btn pb-btn-primary">{{ __('Create plan') }}</button>
        </x-form-modal-actions>
    </x-form-shell>
</x-modal-form>
