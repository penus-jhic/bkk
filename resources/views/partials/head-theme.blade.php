<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Google Fonts: Oswald & Plus Jakarta Sans (Sama persis dengan FeLandingPageJhic) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    display: ['"Oswald"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
                colors: {
                    brand: {
                        darkred: '#7A1018',
                        deepred: '#5C0B12',
                        mist: '#DDDDDD',
                        softmist: '#E8E8E8',
                        ink: '#241012',
                        signal: '#B72A32',
                        warmred: '#D04A43',
                        rose: '#A66B6E',
                    },
                    // Backward-compat aliases
                    maroon: {
                        DEFAULT: '#7A1018',
                        dark: '#5C0B12',
                        light: '#B72A32',
                    },
                    primary: {
                        DEFAULT: '#7A1018',
                        container: '#B72A32',
                        fixed: '#FCE8E8',
                    }
                },
                boxShadow: {
                    softpill: '0 8px 30px -8px rgb(36 16 18 / 0.18)',
                    card: '0 4px 20px -4px rgb(36 16 18 / 0.08)',
                    elevated: '0 20px 40px -15px rgb(36 16 18 / 0.15)',
                },
                borderRadius: {
                    card: '1rem',
                    '2.5rem': '2.5rem',
                },
                animation: {
                    'fade-up': 'fade-up 0.5s ease-out both',
                },
                keyframes: {
                    'fade-up': {
                        'from': { opacity: '0', transform: 'translateY(16px)' },
                        'to': { opacity: '1', transform: 'translateY(0)' },
                    }
                }
            }
        }
    }
</script>

<!-- Alpine.js CDN -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    /* Styling Dasar Mengikuti FeLandingPageJhic */
    [x-cloak] { display: none !important; }
    
    body {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        color: #241012;
        background-color: #FFFFFF;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .font-display {
        font-family: 'Oswald', ui-sans-serif, system-ui, sans-serif;
    }

    /* Scrollbar halus */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #F4F4F6;
    }
    ::-webkit-scrollbar-thumb {
        background: #DDDDDD;
        border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #A66B6E;
    }

    /* Gaya Card dan UI element seragam */
    .card-jhic {
        border-radius: 1rem;
        border: 1px solid rgba(36, 16, 18, 0.1);
        background: #FFFFFF;
        box-shadow: 0 4px 20px -4px rgb(36 16 18 / 0.05);
        transition: all 0.25s ease-in-out;
    }
    .card-jhic:hover {
        border-color: rgba(122, 16, 24, 0.25);
        box-shadow: 0 8px 30px -8px rgb(36 16 18 / 0.12);
    }

    .btn-primary-jhic {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 9999px;
        background: linear-gradient(to right, #B72A32, #7A1018);
        padding: 0.625rem 1.375rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #FFFFFF;
        box-shadow: 0 4px 14px 0 rgba(122, 16, 24, 0.25);
        transition: all 0.2s ease-in-out;
    }
    .btn-primary-jhic:hover {
        box-shadow: 0 6px 20px 0 rgba(122, 16, 24, 0.35);
        transform: translateY(-1px);
    }
    .btn-primary-jhic:active {
        transform: scale(0.98);
    }

    .btn-secondary-jhic {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 9999px;
        border: 1px solid rgba(36, 16, 18, 0.15);
        background: #FFFFFF;
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #241012;
        transition: all 0.2s ease-in-out;
    }
    .btn-secondary-jhic:hover {
        background-color: #E8E8E8;
        border-color: rgba(36, 16, 18, 0.25);
    }

    .input-jhic {
        display: block;
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(36, 16, 18, 0.15);
        background-color: #FFFFFF;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        color: #241012;
        outline: none;
        transition: all 0.15s ease-in-out;
    }
    .input-jhic:focus {
        border-color: #7A1018;
        box-shadow: 0 0 0 4px rgba(122, 16, 24, 0.1);
    }
</style>
