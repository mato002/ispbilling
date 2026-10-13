<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isp->company_name ?? 'Hotspot Portal' }} - {{ $title ?? 'WiFi Access' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <x-vite-assets />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .mpesa-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #bbf7d0;
            border-top-color: #087a48;
            border-radius: 50%;
            animation: mpesa-spin .8s linear infinite;
        }
        @keyframes mpesa-spin { to { transform: rotate(360deg); } }
        .hotspot-payment-modal { border-radius: 18px !important; }
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
    <div class="min-h-screen flex flex-col items-center justify-start p-2 sm:justify-center sm:p-5">
        <div class="portal-container w-full {{ request()->routeIs('hotspot.splash', 'hotspot.packages') ? 'max-w-7xl' : 'max-w-md' }} p-3 sm:p-8">
            @if(isset($portalSettings) && $portalSettings->logo_url)
                <div class="text-center mb-3 sm:mb-6">
                    <img src="{{ $portalSettings->logo_url }}" alt="Portal Logo" class="h-10 sm:h-16 mx-auto mb-2 sm:mb-4">
                </div>
            @elseif(isset($isp) && $isp)
                <div class="text-center mb-3 sm:mb-6">
                    @if($isp->logo)
                        <img src="{{ $isp->logo }}" alt="{{ $isp->company_name }}" class="h-10 sm:h-16 mx-auto mb-2 sm:mb-4">
                    @endif
                    <h1 class="text-base sm:text-2xl font-bold text-gray-900 leading-tight">{{ $isp->company_name ?? $isp->name }}</h1>
                    @if($isp->tagline)
                        <p class="text-sm text-gray-600 mt-1">{{ $isp->tagline }}</p>
                    @endif
                </div>
            @endif

            @if(isset($portalSettings))
                <div class="text-center mb-3 sm:mb-6">
                    <h2 class="text-lg sm:text-2xl font-bold text-gray-900">{{ $portalSettings->welcome_message }}</h2>
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

        <div class="mt-3 w-full max-w-3xl text-center text-[11px] text-white/90 sm:mt-4 sm:text-xs">
            @if(isset($portalSettings) && ($portalSettings->mpesa_till_number || $portalSettings->support_phone || $portalSettings->support_whatsapp))
                <div class="mb-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 rounded-xl bg-slate-950/20 px-3 py-2">
                    @if($portalSettings->mpesa_till_number)
                        <span><strong>M-Pesa Till:</strong> {{ $portalSettings->mpesa_till_number }}</span>
                    @endif
                    @if($portalSettings->support_phone)
                        <a class="underline underline-offset-2" href="tel:{{ preg_replace('/[^0-9+]/', '', $portalSettings->support_phone) }}">Call {{ $portalSettings->support_phone }}</a>
                    @endif
                    @if($portalSettings->support_whatsapp)
                        <a class="underline underline-offset-2" target="_blank" rel="noopener" href="https://wa.me/{{ preg_replace('/^0/', '254', preg_replace('/\D/', '', $portalSettings->support_whatsapp)) }}">WhatsApp support</a>
                    @endif
                </div>
                @if($portalSettings->mpesa_till_number)
                    <p class="mb-2 text-[10px] text-white/80 sm:text-[11px]">If the STK prompt fails, pay to the Till above and contact support with your M-Pesa receipt.</p>
                @endif
            @endif
            <p>Powered by {{ $isp->company_name ?? $isp->name ?? config('app.name') }}</p>
        </div>
    </div>
</body>
</html>





