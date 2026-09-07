<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'GameTopUp'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink">
    <div class="h-0.5 bg-gradient-to-r from-forest-500 via-forest-400 to-forest-600"></div>
    <main class="flex flex-1 flex-col">
        @if(session('success'))
            @push('toasts')
                <div class="toast-message" data-toast-type="success" data-toast-message="{{ session('success') }}"></div>
            @endpush
        @endif
        @if(session('error'))
            @push('toasts')
                <div class="toast-message" data-toast-type="error" data-toast-message="{{ session('error') }}"></div>
            @endpush
        @endif
        @if(session('info'))
            @push('toasts')
                <div class="toast-message" data-toast-type="info" data-toast-message="{{ session('info') }}"></div>
            @endpush
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                @push('toasts')
                    <div class="toast-message" data-toast-type="error" data-toast-message="{{ $error }}"></div>
                @endpush
            @endforeach
        @endif

        @yield('content')
    </main>

    @stack('toasts')
    <div id="toast-region" class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-5 sm:items-end" aria-live="polite"></div>
</body>
</html>
