<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="web_standard" name="shell-type"/>
    <title>@yield('title', 'BKK Penus - Bursa Kerja Khusus SMK Plus Pelita Nusantara')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-penus.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-penus.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-penus.png') }}">
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        @layer base {
            html, body { margin: 0; padding: 0; }
            body { overscroll-behavior: none; }
            main > :first-child { margin-top: 0 !important; }
            main > :last-child { margin-bottom: 0 !important; }
        }
        ::-webkit-scrollbar { display: none; }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-tertiary": "#ffffff",
                        "secondary": "#875300",
                        "inverse-primary": "#ffb4ab",
                        "surface-variant": "#e5e2e0",
                        "surface-container": "#f0edeb",
                        "on-secondary-container": "#6a4000",
                        "primary-fixed": "#ffdad6",
                        "on-primary-fixed": "#410002",
                        "primary": "#bc0013",
                        "on-tertiary-fixed": "#111c2e",
                        "surface-container-highest": "#e5e2e0",
                        "surface-tint": "#c00014",
                        "surface-container-high": "#ebe8e5",
                        "surface-dim": "#dcd9d7",
                        "surface": "#fcf9f6",
                        "on-background": "#1c1c1a",
                        "on-surface": "#1c1c1a",
                        "on-secondary-fixed-variant": "#663d00",
                        "on-tertiary-container": "#fffcff",
                        "surface-bright": "#fcf9f6",
                        "on-error-container": "#93000a",
                        "tertiary-fixed": "#d8e2fc",
                        "secondary-fixed-dim": "#ffb965",
                        "tertiary-container": "#6b758b",
                        "on-primary-container": "#fffcff",
                        "on-error": "#ffffff",
                        "outline": "#946e6a",
                        "on-tertiary-fixed-variant": "#3d475b",
                        "primary-fixed-dim": "#ffb4ab",
                        "on-secondary": "#ffffff",
                        "background": "#fcf9f6",
                        "surface-container-lowest": "#ffffff",
                        "on-primary-fixed-variant": "#93000c",
                        "secondary-container": "#ffa525",
                        "primary-container": "#eb001b",
                        "outline-variant": "#e9bcb7",
                        "surface-container-low": "#f6f3f1",
                        "on-primary": "#ffffff",
                        "tertiary": "#525c72",
                        "on-secondary-fixed": "#2b1700",
                        "inverse-on-surface": "#f3f0ee",
                        "error-container": "#ffdad6",
                        "tertiary-fixed-dim": "#bcc6df",
                        "error": "#ba1a1a",
                        "inverse-surface": "#31302f",
                        "secondary-fixed": "#ffddba",
                        "on-surface-variant": "#5f3f3b"
                    },
                    borderRadius: {
                        DEFAULT: "1rem",
                        lg: "2rem",
                        xl: "3rem",
                        full: "9999px"
                    },
                    spacing: {
                        "space-lg": "1.5rem",
                        "gutter": "1.5rem",
                        "margin-mobile": "1.25rem",
                        "gutter-mobile": "1rem",
                        "margin": "2rem",
                        "space-xl": "2.5rem",
                        "space-md": "1rem",
                        "margin-desktop-max": "5rem",
                        "space-sm": "0.5rem",
                        "space-xs": "0.25rem"
                    },
                    fontFamily: {
                        "title-md": ["Inter"],
                        "headline-sm": ["Hanken Grotesk"],
                        "body-default": ["Inter"],
                        "headline-lg": ["Hanken Grotesk"],
                        "metric-stat": ["Hanken Grotesk"],
                        "body-dense": ["Inter"],
                        "body-editorial": ["Inter"],
                        "label-md": ["Inter"],
                        "label-dense": ["Inter"],
                        "headline-xl-mobile": ["Hanken Grotesk"],
                        "headline-xl": ["Hanken Grotesk"],
                        "display-hero-mobile": ["Hanken Grotesk"],
                        "display-hero": ["Hanken Grotesk"],
                        "headline-md": ["Hanken Grotesk"]
                    },
                    fontSize: {
                        "title-md": ["18px", { lineHeight: "24px", fontWeight: "600" }],
                        "headline-sm": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                        "body-default": ["15px", { lineHeight: "22px", fontWeight: "400" }],
                        "headline-lg": ["32px", { lineHeight: "40px", fontWeight: "600" }],
                        "metric-stat": ["28px", { lineHeight: "34px", fontWeight: "700" }],
                        "body-dense": ["13px", { lineHeight: "18px", fontWeight: "400" }],
                        "body-editorial": ["18px", { lineHeight: "30px", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "20px", fontWeight: "500" }],
                        "label-dense": ["12px", { lineHeight: "16px", fontWeight: "600" }],
                        "headline-xl-mobile": ["32px", { lineHeight: "40px", fontWeight: "600" }],
                        "headline-xl": ["44px", { lineHeight: "52px", fontWeight: "600" }],
                        "display-hero-mobile": ["40px", { lineHeight: "48px", fontWeight: "700" }],
                        "display-hero": ["64px", { lineHeight: "72px", fontWeight: "700" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }]
                    }
                }
            }
        };
    </script>
    @stack('styles')
</head>
<body class="bg-surface font-body-default text-body-default text-on-surface antialiased">
    @include('index.partials.header')

    <main class="w-full pt-20 bg-surface min-h-[calc(100vh-20rem)]">
        @yield('content')
    </main>

    @include('index.partials.footer')

    @stack('scripts')
</body>
</html>
