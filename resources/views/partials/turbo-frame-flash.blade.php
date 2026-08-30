@php
    $pbFlashPayload = [
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
        'flash_modal' => session('flash_modal'),
        'validation_errors' => (isset($errors) && $errors->any()) ? $errors->all() : null,
    ];
@endphp
<script type="application/json" id="pb-flash-payload">@json($pbFlashPayload)</script>
