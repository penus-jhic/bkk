<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="web_standard" name="shell-type"/>
    <title>@yield('title', 'Admin BKK - SMK Plus Pelita Nusantara')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="icon" href="{{ asset('images/logosmkpenus.png') }}">

    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>

    <!-- Lucide Icons (versi dikunci supaya nama ikon tidak berubah diam-diam) -->
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script id="tailwind-config">
        // Palet & tipografi mengikuti landing page BKK (gaya coretan). Token lama (navy, maroon, line, canvas, muted)
        // tetap ada tapi diarahkan ke warna brand, jadi semua halaman admin ikut berganti tampilan.
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        brand: {
                            darkred: "#7A1018",
                            deepred: "#5C0B12",
                            mist: "#DDDDDD",
                            softmist: "#E8E8E8",
                            ink: "#241012",
                            signal: "#B72A32",
                            warmred: "#D04A43",
                            rose: "#A66B6E",
                            paper: "#F5F4F2",
                        },
                        navy: {
                            DEFAULT: "#241012",
                            dark: "#140809",
                            light: "#3a1d20",
                        },
                        maroon: {
                            DEFAULT: "#7A1018",
                            dark: "#5C0B12",
                            light: "#B72A32",
                        },
                        line: "#DDDDDD",
                        canvas: "#F5F4F2",
                        muted: "#6f5e60",

                        // Material Theme compatibility for existing modules
                        "primary": "#7A1018",
                        "primary-container": "#B72A32",
                        "primary-fixed": "#FCE8E8",
                        "on-primary": "#ffffff",
                        "secondary": "#875300",
                        "secondary-container": "#ffa525",
                        "surface": "#F5F4F2",
                        "surface-container": "#efedea",
                        "surface-container-low": "#F5F4F2",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-high": "#ebe8e5",
                        "surface-container-highest": "#e5e2e0",
                        "surface-variant": "#DDDDDD",
                        "on-surface": "#241012",
                        "on-surface-variant": "#6f5e60",
                        "outline": "#DDDDDD",
                        "outline-variant": "#e9bcb7",
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', "ui-sans-serif", "system-ui", "sans-serif"],
                        display: ["Oswald", "ui-sans-serif", "system-ui", "sans-serif"],
                        "title-md": ['"Plus Jakarta Sans"'],
                        "headline-sm": ["Oswald"],
                        "body-default": ['"Plus Jakarta Sans"'],
                        "headline-lg": ["Oswald"],
                        "headline-md": ["Oswald"],
                        "headline-xl": ["Oswald"],
                        "metric-stat": ["Oswald"],
                    },
                    boxShadow: {
                        softpill: "0 8px 30px -8px rgb(36 16 18 / 0.18)",
                        card: "0 4px 20px -4px rgb(36 16 18 / 0.08)",
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem",
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                        card: '1rem',
                        full: "9999px"
                    }
                }
            }
        };
    </script>

    <style>
        html, body {
            background-color: #F5F4F2;
            color: #241012;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        ::selection { background-color: #7A1018; color: #fff; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F5F4F2; }
        ::-webkit-scrollbar-thumb { background: #DDDDDD; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #A66B6E; }

        @keyframes shimmer {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }
        .shimmer {
            background: linear-gradient(90deg, #f1efed 0%, #e8e5e2 40%, #f1efed 80%);
            background-size: 800px 100%;
            animation: shimmer 1.2s infinite linear;
        }

        .gemini-text {
            background: linear-gradient(90deg, #7A1018, #241012 60%, #7A1018);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .gemini-border {
            background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #7A1018, #241012, #DDDDDD) border-box;
            border: 1px solid transparent;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }
        .fade-up {
            animation: fadeUp .3s ease-out both;
        }

        /* Judul halaman & section admin memakai tipografi display landing page (Oswald kapital) */
        #main-scroll h1,
        #main-scroll h2 {
            font-family: 'Oswald', ui-sans-serif, system-ui, sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-weight: 600;
        }
        #main-scroll h1 { font-weight: 700; }

        @media (prefers-reduced-motion: reduce) {
            .fade-up { animation: none; }
        }
    </style>
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
                <span class="flex-1">${msg}</span>
            `;
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
