@if ($errors->any())
    <div data-pb-onboarding-alert data-icon="error" data-title="Check your details" data-message="{{ $errors->first() }}" hidden></div>
@elseif (session('success'))
    <div data-pb-onboarding-alert data-icon="success" data-title="Step saved" data-message="{{ session('success') }}" hidden></div>
@elseif (session('error'))
    <div data-pb-onboarding-alert data-icon="error" data-title="Could not continue" data-message="{{ session('error') }}" hidden></div>
@elseif (session('warning'))
    <div data-pb-onboarding-alert data-icon="warning" data-title="Please note" data-message="{{ session('warning') }}" hidden></div>
@elseif (session('info'))
    <div data-pb-onboarding-alert data-icon="info" data-title="Setup update" data-message="{{ session('info') }}" hidden></div>
@endif
