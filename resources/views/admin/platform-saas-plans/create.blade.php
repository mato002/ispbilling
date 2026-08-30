<x-modal-form :title="__('Create SaaS plan')" :cancel-url="route('admin.platform-saas-plans.index')" maxWidth="5xl">
    <x-form-shell :action="route('admin.platform-saas-plans.store')">
        @include('admin.platform-saas-plans._form', [
            'defaultTrialDays' => $defaultTrialDays,
            'cancelUrl' => route('admin.platform-saas-plans.index'),
        ])
    </x-form-shell>
</x-modal-form>
