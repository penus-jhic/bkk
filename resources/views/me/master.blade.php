<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="web_standard" name="shell-type"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title', 'Portal Siswa & Alumni - BKK SMK Plus Pelita Nusantara')</title>

    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        navy: {
                            DEFAULT: "#1b283b",
                            dark: "#101a29",
                            light: "#25374e",
                        },
                        maroon: {
                            DEFAULT: "#741918",
                            dark: "#5e1413",
                            light: "#8a201f",
                        },
                        line: "#dcdcdc",
                        canvas: "#f8f9fa",
                        muted: "#5f6368",
                    },
                    fontFamily: {
                        sans: ["Inter", "Roboto", "system-ui", "sans-serif"],
                    },
                    borderRadius: {
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                    }
                }
            }
        };
    </script>

    <style>
        html, body {
            background-color: #f8f9fa;
            color: #1b283b;
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f8f9fa; }
        ::-webkit-scrollbar-thumb { background: #dcdcdc; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #b0b0b0; }

        @keyframes shimmer {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }
        .shimmer {
            background: linear-gradient(90deg, #f1f3f4 0%, #e8eaed 40%, #f1f3f4 80%);
            background-size: 800px 100%;
            animation: shimmer 1.2s infinite linear;
        }

        .gemini-text {
            background: linear-gradient(90deg, #741918, #1b283b 60%, #741918);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .gemini-border {
            background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #741918, #1b283b, #dcdcdc) border-box;
            border: 1px solid transparent;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }
        .fade-up {
            animation: fadeUp .3s ease-out both;
        }

        /* Markdown rendering inside CV */
        .md-cv h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; color: #1b283b; }
        .md-cv h2 { font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #741918; border-bottom: 1px solid #dcdcdc; padding-bottom: .25rem; margin: 1rem 0 .5rem; }
        .md-cv h3 { font-size: .95rem; font-weight: 600; margin-top: .5rem; color: #1b283b; }
        .md-cv p { font-size: .82rem; line-height: 1.55; color: #3c4043; margin: .25rem 0; }
        .md-cv ul { list-style: disc; padding-left: 1.1rem; margin: .25rem 0; }
        .md-cv li { font-size: .82rem; line-height: 1.5; color: #3c4043; }
        .md-cv strong { color: #1b283b; }
        .md-cv em { color: #5f6368; }
        .md-cv a { color: #741918; text-decoration: underline; }
        .md-cv hr { border-color: #dcdcdc; margin: .75rem 0; }
    </style>
    @stack('styles')
</head>
<body class="bg-canvas text-navy font-sans antialiased min-h-screen flex flex-col">
    <!-- Top Bar Navigation -->
    @include('me.partials.header')

    <!-- Main Container with Sidebar + Main Content -->
    <div class="flex flex-1 min-h-[calc(100vh-4rem)]">
        <!-- Sidebar Navigation -->
        @include('me.partials.sidebar')

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
                <span class="flex-1">${msg}</span>
            `;
            container.appendChild(toast);
            lucide.createIcons({ root: toast });

            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Initialize Lucide Icons on DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
