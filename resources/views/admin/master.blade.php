<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="web_standard" name="shell-type"/>
    <title>@yield('title', 'Admin BKK - SMK Plus Pelita Nusantara')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @include('partials.dashboard-theme')
    <!-- Ikon Material dipakai editor berita -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
    @stack('styles')
</head>
<body class="bg-canvas text-navy font-sans antialiased min-h-screen flex flex-col">
    <!-- Top Bar Navigation -->
    @include('admin.partials.header')

    <!-- Main Container with Sidebar + Main Content -->
    <div class="flex flex-1 min-h-[calc(100vh-4rem)]">
        <!-- Sidebar Navigation -->
        @include('admin.partials.sidebar')

        <!-- Dynamic Content Area -->
        <main id="main-scroll" class="flex-1 min-w-0 overflow-y-auto">
            <div class="max-w-[1400px] mx-auto p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center gap-2 font-semibold mb-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 shrink-0"></i>
                            <span>Terdapat kesalahan pengisian formulir:</span>
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[60] space-y-2 w-[min(92vw,420px)] pointer-events-none"></div>

    <script>
        // Global Toast Notification Helper
        function showToast(msg, duration = 3000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'fade-up bg-navy text-white text-sm rounded-xl px-4 py-3 shadow-xl flex items-center gap-3 pointer-events-auto border border-line/20';
            toast.innerHTML = `
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span class="flex-1 toast-msg"></span>
            `;
            toast.querySelector('.toast-msg').textContent = String(msg ?? '');
            container.appendChild(toast);
            if (window.lucide) {
                lucide.createIcons({ root: toast });
            }

            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Initialize Lucide Icons on DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
    @include('partials.dev-auth-banner')
    @include('partials.sketch-engine')
    @stack('scripts')
</body>
</html>
