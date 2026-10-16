<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="web_standard" name="shell-type"/>
    <title>@yield('title', 'Portal Guru Pembimbing PKL - BKK SMK Plus Pelita Nusantara')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @include('partials.dashboard-theme')
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest"></script>
    @stack('styles')
</head>
<body class="bg-canvas text-navy font-sans antialiased min-h-screen flex flex-col">
    <!-- Top Bar Navigation -->
    @include('guru.partials.header')

    <!-- Main Container with Sidebar + Main Content -->
    <div class="flex flex-1 min-h-[calc(100vh-4rem)]">
        <!-- Sidebar Navigation -->
        @include('guru.partials.sidebar')

        <!-- Dynamic Content Area -->
        <main id="main-scroll" class="flex-1 min-w-0 overflow-y-auto">
            <div class="max-w-[1400px] mx-auto p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                            <span class="font-medium text-sm">{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">×</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
                            <span class="font-medium text-sm">{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800 text-xs font-bold">×</button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-xs">
                        <div class="flex items-center gap-2 font-semibold mb-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 shrink-0"></i>
                            <span>Terdapat kendala pengisian formulir:</span>
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

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
