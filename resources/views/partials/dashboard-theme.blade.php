{{-- Tema bersama semua dashboard (Admin BKK, Portal Mitra, Portal Siswa), disamakan dengan gaya landing page
     (index/layouts/landing.blade.php): tinta hangat #241012, abu "mist" hangat, aksen merah darkred → signal,
     tipografi Oswald kapital + Plus Jakarta Sans, dan coretan tangan (x-sketch.*).
     Satu-satunya warna di luar palet landing adalah "navy": warna tombol aksi sekunder dashboard (bg-navy).

     Token lama (navy, maroon, line, canvas, muted, primary/surface ala Material, font-headline, .google-card) tetap ada
     tapi diarahkan ke palet yang sama, jadi halaman lama ikut berganti tampilan tanpa ditulis ulang. Skala bawaan
     Tailwind red/rose/slate/gray juga ditimpa ke versi hangat supaya kelas lama (bg-red-50, text-slate-500) ikut serasi. --}}
<link rel="icon" href="{{ asset('images/logosmkpenus.png') }}">

<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>

<!-- Lucide Icons (versi dikunci supaya nama ikon tidak berubah diam-diam) -->
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com/3.4.16"></script>
<script id="tailwind-config">
    // Netral hangat (abu kemerahan seperti landing), dipakai untuk slate & gray
    const warmNeutral = {
        50: "#FAF8F7", 100: "#F4F0EF", 200: "#E8E2E1", 300: "#D6CDCC", 400: "#A89C9D",
        500: "#7A6D6E", 600: "#5E5253", 700: "#463B3C", 800: "#2F2526", 900: "#241012", 950: "#170A0B",
    };
    // Merah brand: 400 = warmred, 500 = signal, 700 = darkred, 800 = deepred
    const brandRed = {
        50: "#FCF2F2", 100: "#F9E1E2", 200: "#F2C3C6", 300: "#E6969B", 400: "#D04A43",
        500: "#B72A32", 600: "#9E1C24", 700: "#7A1018", 800: "#5C0B12", 900: "#430810", 950: "#2A0509",
    };

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
                        rosedeep: "#7D4A4D",
                        blush: "#F5C2C7",
                        ink: "#241012",
                        navy: "#1E293B",
                        blue: "#1E293B",
                        mist: "#DDDDDD",
                        softmist: "#E8E8E8",
                        paper: "#F6F3F2",
                        muted: "#76696A",
                    },
                    // Navy = warna tombol (bg-navy); teks gelap memakai brand-ink
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
                    line: "#EAE4E3",
                    canvas: "#F6F3F2",
                    muted: "#76696A",

                    red: brandRed,
                    rose: brandRed,
                    slate: warmNeutral,
                    gray: warmNeutral,

                    // Kompatibilitas token Material untuk modul lama
                    "primary": "#7A1018",
                    "primary-container": "#B72A32",
                    "primary-fixed": "#FCE8E8",
                    "on-primary": "#ffffff",
                    "secondary": "#875300",
                    "secondary-container": "#ffa525",
                    "surface": "#F6F3F2",
                    "surface-container": "#F1ECEB",
                    "surface-container-low": "#F6F3F2",
                    "surface-container-lowest": "#ffffff",
                    "surface-container-high": "#EDE7E6",
                    "surface-container-highest": "#E8E2E1",
                    "surface-variant": "#E8E2E1",
                    "on-surface": "#241012",
                    "on-surface-variant": "#76696A",
                    "outline": "#EAE4E3",
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
                    softpill: "0 8px 30px -8px rgb(36 16 18 / 0.18)",
                    card: "0 4px 20px -4px rgb(36 16 18 / 0.06)",
                    elevated: "0 20px 40px -15px rgb(36 16 18 / 0.15)",
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
        background-color: #FFFFFF;
        color: #241012;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
    }

    /* Area konten: putih dengan grid halus + semburat merah, sama dengan latar hero landing */
    #main-scroll {
        background-color: #FFFFFF;
        background-image:
            radial-gradient(circle at 100% 0%, rgb(183 42 50 / 0.07), transparent 32rem),
            linear-gradient(to right, rgb(36 16 18 / 0.035) 1px, transparent 1px),
            linear-gradient(to bottom, rgb(36 16 18 / 0.035) 1px, transparent 1px);
        background-size: 100% 100%, 44px 44px, 44px 44px;
        background-attachment: local;
    }

    ::selection { background-color: #7A1018; color: #fff; }

    :focus-visible { outline: 2px solid #7A1018; outline-offset: 2px; }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #F6F3F2; }
    ::-webkit-scrollbar-thumb { background: #DDDDDD; border-radius: 999px; }
    ::-webkit-scrollbar-thumb:hover { background: #A66B6E; }

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
        background: linear-gradient(90deg, #F4F0EF 0%, #E8E2E1 40%, #F4F0EF 80%);
        background-size: 800px 100%;
        animation: shimmer 1.2s infinite linear;
    }

    .gemini-text {
        background: linear-gradient(90deg, #B72A32, #7A1018 55%, #241012);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .gemini-border {
        background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #B72A32, #7A1018, #E8E8E8) border-box;
        border: 1px solid transparent;
    }

    /* Kartu dashboard, sama dengan kartu landing (rounded-card, garis tinta tipis, bayangan lembut) */
    .google-card {
        background-color: #ffffff;
        border: 1px solid rgb(36 16 18 / 0.1);
        border-radius: 1rem;
        box-shadow: 0 4px 20px -4px rgb(36 16 18 / 0.06);
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .google-card:hover {
        border-color: rgb(122 16 24 / 0.25);
        box-shadow: 0 8px 30px -8px rgb(36 16 18 / 0.16);
    }

    /* Label kecil di atas judul (eyebrow), sama dengan "Bursa Kerja Khusus & ..." di hero landing.
       Breadcrumb halaman memakai kelas ini juga. */
    .dash-eyebrow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.375rem 0.5rem;
        margin-bottom: 0.625rem;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: rgb(122 16 24 / 0.65);
    }
    .dash-eyebrow a { transition: color .15s ease; }
    .dash-eyebrow a:hover { color: #7A1018; }
    .dash-eyebrow > span:last-child, .dash-eyebrow strong { color: #7A1018; }
    .dash-eyebrow svg, .dash-eyebrow i { width: 0.75rem; height: 0.75rem; }

    /* Judul halaman & section memakai tipografi display (Oswald kapital), sama di semua dashboard */
    .font-headline { text-transform: uppercase; letter-spacing: 0.02em; }
    #main-scroll h1,
    #main-scroll h2 {
        font-family: 'Oswald', ui-sans-serif, system-ui, sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        font-weight: 600;
        color: #241012;
    }
    #main-scroll h1 { font-weight: 700; line-height: 1.1; }
    /* Judul halaman: lebih besar seperti judul section landing, coretan bawah dari x-sketch.underline */
    #main-scroll h1.dash-title { font-size: 1.875rem; padding-bottom: 0.875rem; }
    @media (min-width: 640px) { #main-scroll h1.dash-title { font-size: 2.25rem; } }

    @media (prefers-reduced-motion: reduce) {
        .fade-up { animation: none; }
        .shimmer { animation: none; }
    }
</style>
