<x-modal-form :title="__('New announcement')" :cancel-url="route('admin.announcements.index')" maxWidth="3xl">
    <x-form-shell :action="route('admin.announcements.store')">
        @include('admin.announcements._form', ['announcement' => null, 'bare' => true])
        <x-form-modal-actions :cancel-url="route('admin.announcements.index')">
            <button type="submit" class="pb-btn pb-btn-primary">{{ __('Publish') }}</button>
        </x-form-modal-actions>
    </x-form-shell>
</x-modal-form>
