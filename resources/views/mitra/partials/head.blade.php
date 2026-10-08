{{-- Kepala dokumen portal mitra (dashboard & halaman login): font, ikon, Tailwind & token warna gaya coretan BKK.
     Token lama (navy, maroon, line, canvas, muted, font-headline, .google-card) tetap ada tapi diarahkan ke warna brand,
     jadi halaman mitra yang belum ditulis ulang ikut berganti tampilan. --}}
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<link rel="icon" href="{{ asset('images/logosmkpenus.png') }}">

<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>

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
                },
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', "ui-sans-serif", "system-ui", "sans-serif"],
                    display: ["Oswald", "ui-sans-serif", "system-ui", "sans-serif"],
                    headline: ["Oswald", "ui-sans-serif", "system-ui", "sans-serif"],
                },
                boxShadow: {
                    softpill: "0 8px 30px -8px rgb(36 16 18 / 0.18)",
                    card: "0 4px 20px -4px rgb(36 16 18 / 0.08)",
                },
                borderRadius: {
                    'xl': '0.75rem',
                    '2xl': '1rem',
                    '3xl': '1.5rem',
                    card: '1rem',
                },
            },
        },
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

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #F5F4F2; }
    ::-webkit-scrollbar-thumb { background: #DDDDDD; border-radius: 999px; }
    ::-webkit-scrollbar-thumb:hover { background: #A66B6E; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: none; }
    }
    .fade-up { animation: fadeUp .3s ease-out both; }

    /* Kartu bawaan halaman mitra lama, disamakan dengan kartu admin */
    .google-card {
        background-color: #ffffff;
        border: 1px solid rgb(36 16 18 / 0.1);
        border-radius: 1rem;
        box-shadow: 0 4px 20px -4px rgb(36 16 18 / 0.08);
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .google-card:hover {
        border-color: rgb(122 16 24 / 0.25);
        box-shadow: 0 8px 30px -8px rgb(36 16 18 / 0.18);
    }

    /* Judul memakai tipografi display landing page (Oswald kapital) */
    .font-headline { text-transform: uppercase; letter-spacing: 0.02em; }

    @media (prefers-reduced-motion: reduce) {
        .fade-up { animation: none; }
    }
</style>
