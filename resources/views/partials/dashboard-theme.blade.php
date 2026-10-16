{{-- Tema bersama semua dashboard (Admin BKK, Portal Mitra, Portal Siswa) dan sama dengan dashboard PPDB & CMS landing page.
     Palet dashboard: maroon sebagai warna utama, navy sebagai warna gelap (teks & permukaan gelap), netral slate.
     Kalau mengubah nilai di sini, ubah juga blok ".dash-theme" di ppdb/resources/css/app.css dan lpfe/src/styles/global.css.

     Token lama (navy, maroon, line, canvas, muted, primary/surface ala Material, font-headline, .google-card) tetap ada
     tapi diarahkan ke palet yang sama, jadi halaman lama ikut berganti tampilan tanpa ditulis ulang. --}}
<link rel="icon" href="{{ asset('images/logosmkpenus.png') }}">

<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>

<!-- Lucide Icons (versi dikunci supaya nama ikon tidak berubah diam-diam) -->
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com/3.4.16"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    brand: {
                        darkred: "#7A1018",
                        deepred: "#5C0B12",
                        signal: "#B72A32",
                        warmred: "#D04A43",
                        rose: "#A66B6E",
                        ink: "#1E293B",
                        navy: "#1E293B",
                        blue: "#2563EB",
                        mist: "#E2E8F0",
                        softmist: "#EEF2F6",
                        paper: "#F5F7FA",
                        muted: "#64748B",
                    },
                    navy: {
                        DEFAULT: "#1E293B",
                        dark: "#0F172A",
                        light: "#334155",
                    },
                    maroon: {
                        DEFAULT: "#7A1018",
                        dark: "#5C0B12",
                        light: "#B72A32",
                    },
                    line: "#E2E8F0",
                    canvas: "#F5F7FA",
                    muted: "#64748B",

                    // Kompatibilitas token Material untuk modul lama
                    "primary": "#7A1018",
                    "primary-container": "#B72A32",
                    "primary-fixed": "#FCE8E8",
                    "on-primary": "#ffffff",
                    "secondary": "#875300",
                    "secondary-container": "#ffa525",
                    "surface": "#F5F7FA",
                    "surface-container": "#EEF2F6",
                    "surface-container-low": "#F5F7FA",
                    "surface-container-lowest": "#ffffff",
                    "surface-container-high": "#E9EDF2",
                    "surface-container-highest": "#E2E8F0",
                    "surface-variant": "#E2E8F0",
                    "on-surface": "#1E293B",
                    "on-surface-variant": "#64748B",
                    "outline": "#E2E8F0",
                    "outline-variant": "#e9bcb7",
                },
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', "ui-sans-serif", "system-ui", "sans-serif"],
                    display: ["Oswald", "ui-sans-serif", "system-ui", "sans-serif"],
                    headline: ["Oswald", "ui-sans-serif", "system-ui", "sans-serif"],
                    "title-md": ['"Plus Jakarta Sans"'],
                    "body-default": ['"Plus Jakarta Sans"'],
                    "headline-sm": ["Oswald"],
                    "headline-md": ["Oswald"],
                    "headline-lg": ["Oswald"],
                    "headline-xl": ["Oswald"],
                    "metric-stat": ["Oswald"],
                },
                boxShadow: {
                    softpill: "0 8px 30px -8px rgb(15 23 42 / 0.16)",
                    card: "0 4px 20px -4px rgb(15 23 42 / 0.06)",
                },
                borderRadius: {
                    DEFAULT: "0.75rem",
                    'xl': '0.75rem',
                    '2xl': '1rem',
                    '3xl': '1.5rem',
                    card: '1rem',
                    full: "9999px",
                },
            },
        },
    };
</script>

<style>
    html, body {
        background-color: #F5F7FA;
        color: #1E293B;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
    }

    ::selection { background-color: #7A1018; color: #fff; }

    :focus-visible { outline: 2px solid #7A1018; outline-offset: 2px; }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #F5F7FA; }
    ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 999px; }
    ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: none; }
    }
    .fade-up { animation: fadeUp .3s ease-out both; }

    @keyframes shimmer {
        0% { background-position: -400px 0; }
        100% { background-position: 400px 0; }
    }
    .shimmer {
        background: linear-gradient(90deg, #EEF2F6 0%, #E2E8F0 40%, #EEF2F6 80%);
        background-size: 800px 100%;
        animation: shimmer 1.2s infinite linear;
    }

    .gemini-text {
        background: linear-gradient(90deg, #7A1018, #1E293B 60%, #7A1018);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .gemini-border {
        background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #7A1018, #1E293B, #E2E8F0) border-box;
        border: 1px solid transparent;
    }

    /* Kartu bawaan halaman lama, disamakan dengan kartu dashboard */
    .google-card {
        background-color: #ffffff;
        border: 1px solid rgb(30 41 59 / 0.1);
        border-radius: 1rem;
        box-shadow: 0 4px 20px -4px rgb(15 23 42 / 0.06);
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .google-card:hover {
        border-color: rgb(122 16 24 / 0.25);
        box-shadow: 0 8px 30px -8px rgb(15 23 42 / 0.16);
    }

    /* Judul halaman & section memakai tipografi display (Oswald kapital), sama di semua dashboard */
    .font-headline { text-transform: uppercase; letter-spacing: 0.02em; }
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
        .shimmer { animation: none; }
    }
</style>
