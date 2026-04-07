<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isp->company_name ?? 'Hotspot Portal' }} - {{ $title ?? 'WiFi Access' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            @if(isset($portalSettings))
                @if($portalSettings->background_type === 'gradient')
                    background: {{ $portalSettings->background_value }};
                @elseif($portalSettings->background_type === 'image')
                    background: url('{{ $portalSettings->background_value }}') no-repeat center center fixed;
                    background-size: cover;
                @else
                    background: {{ $portalSettings->background_value }};
                @endif
            @else
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            @endif
            min-height: 100vh;
        }
        .portal-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        @if(isset($portalSettings))
            .btn-primary {
                background-color: {{ $portalSettings->theme_color }} !important;
                border-color: {{ $portalSettings->theme_color }} !important;
            }
            .btn-primary:hover {
                background-color: {{ $portalSettings->theme_color }}dd !important;
                border-color: {{ $portalSettings->theme_color }}dd !important;
            }
        @endif
        @if(isset($portalSettings) && $portalSettings->custom_css)
            @foreach($portalSettings->custom_css as $css)
                {{ $css }}
            @endforeach
        @endif
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="portal-container w-full max-w-md p-6 sm:p-8">
            @if(isset($portalSettings) && $portalSettings->logo_url)
                <div class="text-center mb-6">
                    <img src="{{ $portalSettings->logo_url }}" alt="Portal Logo" class="h-16 mx-auto mb-4">
                </div>
            @elseif(isset($isp) && $isp)
                <div class="text-center mb-6">
                    @if($isp->logo)
                        <img src="{{ $isp->logo }}" alt="{{ $isp->company_name }}" class="h-16 mx-auto mb-4">
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900">{{ $isp->company_name ?? $isp->name }}</h1>
                    @if($isp->tagline)
                        <p class="text-sm text-gray-600 mt-1">{{ $isp->tagline }}</p>
                    @endif
                </div>
            @endif

            @if(isset($portalSettings))
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $portalSettings->welcome_message }}</h2>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    @if(session('error'))
                        <p class="text-sm text-red-800">{{ session('error') }}</p>
                    @endif
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-800">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </div>

        <div class="mt-4 text-center text-[11px] text-white/80">
            <p>
                Powered by {{ $isp->company_name ?? $isp->name ?? config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>





