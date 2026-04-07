<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel')) - Admin</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // SweetAlert2 Global Configuration and Helpers
            window.Swal = Swal.mixin({
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                denyButtonColor: '#ef4444',
                buttonsStyling: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
            });

            // Helper function for confirmation dialogs
            window.confirmAction = function(options) {
                const defaults = {
                    title: 'Are you sure?',
                    text: 'This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6b7280',
                };

                const config = Object.assign({}, defaults, options);
                
                return Swal.fire(config);
            };

            // Form confirmation helper
            window.confirmFormSubmit = function(event, options) {
                event.preventDefault();
                const form = event.target;
                
                confirmAction(options).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            };
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <!-- Header -->
        @include('layouts.admin-header')

        <div class="flex h-screen pt-16">
            <!-- Sidebar -->
            @include('layouts.admin-sidebar')

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
                <!-- Page Content -->
                <div class="p-6">
                    @hasSection('header')
                        <div class="bg-white dark:bg-gray-800 shadow mb-6">
                            <div class="px-6 py-4">
                                @yield('header')
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>

        @stack('scripts')
        
        <script>
            // Handle Laravel flash messages with SweetAlert
            document.addEventListener('DOMContentLoaded', function() {
                // Success messages
                @if(session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#10b981',
                        timer: 3000,
                        timerProgressBar: true,
                    });
                @endif

                // Error messages
                @if(session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: '{{ session('error') }}',
                        confirmButtonColor: '#ef4444',
                    });
                @endif

                // Warning messages
                @if(session('warning'))
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning!',
                        text: '{{ session('warning') }}',
                        confirmButtonColor: '#f59e0b',
                    });
                @endif

                // Info messages
                @if(session('info'))
                    Swal.fire({
                        icon: 'info',
                        title: 'Information',
                        text: '{{ session('info') }}',
                        confirmButtonColor: '#3b82f6',
                    });
                @endif

                // Validation errors
                @if(isset($errors) && $errors->any())
                    const errorMessages = @json($errors->all());
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<ul style="text-align: left; margin-top: 10px; list-style: disc; padding-left: 20px;">' + 
                              errorMessages.map(msg => '<li style="margin-bottom: 5px;">' + msg + '</li>').join('') + 
                              '</ul>',
                        confirmButtonColor: '#ef4444',
                    });
                @endif
            });
        </script>
    </body>
</html>

