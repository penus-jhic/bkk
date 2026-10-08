{{--
    Layout halaman publik BKK bergaya landing page sekolah (FeLandingPageJhic): Tailwind v4, navbar, footer,
    komponen Alpine & mesin coretan tangan (data-sketch). Dipakai beranda dan halaman berita.
    Halaman boleh mengisi variabel di bawah lebih dulu (mis. $whatsappUrl); yang belum diisi memakai nilai bawaan.
    $navSection = item dropdown BKK yang ditandai aktif (id section di beranda, mis. 'berita').
--}}
@php
    // Situs sekolah (FeLandingPageJhic) & PPDB dipasang di domain yang sama dengan BKK, jadi tautannya path relatif
    // seperti src/data/navigation.ts. Isi config app.school_site_url kalau situs sekolah dipindah ke domain lain.
    $schoolSite ??= rtrim((string) config('app.school_site_url', ''), '/');
    $schoolUrl ??= 'https://smkpluspnb.sch.id'; // website resmi sekolah (footer & media sosial)
    $ppdbUrl ??= $schoolSite . '/ppdb';
    $whatsappUrl ??= 'https://wa.me/6281210868958';
    $portalUrl ??= route('bkk.me.index');
    $navSection ??= 'beranda';

    // Urutan & slug sama dengan src/data/majors.ts
    $majors ??= [
        ['code' => 'MM', 'name' => 'Multimedia', 'slug' => 'multimedia'],
        ['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak', 'slug' => 'rekayasa-perangkat-lunak'],
        ['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan', 'slug' => 'teknik-komputer-jaringan'],
        ['code' => 'PKM', 'name' => 'Perbankan dan Keuangan Mikro', 'slug' => 'perbankan-keuangan-mikro'],
        ['code' => 'TOI', 'name' => 'Teknik Otomasi Industri', 'slug' => 'teknik-otomasi-industri'],
    ];

    // Menu utama, isinya sama dengan navItems di src/data/navigation.ts. Bedanya hanya "BKK": di situs sekolah berupa
    // satu tautan, di sini dropdown berisi section & halaman BKK.
    // section = id section di beranda (menu aktif mengikuti scroll di beranda, $navSection di halaman lain), divider = garis pemisah sebelum tautan itu.
    $bkkHome = route('bkk.index');
    $navItems = [
        ['label' => 'Beranda', 'href' => $schoolSite . '/'],
        ['label' => 'Profil', 'links' => [
            ['label' => 'Tentang Kami', 'href' => $schoolSite . '/tentang'],
            ['label' => 'Profil Guru', 'href' => $schoolSite . '/profil-guru'],
            ['label' => 'Fasilitas', 'href' => $schoolSite . '/fasilitas'],
        ]],
        ['label' => 'Jurusan', 'links' => array_map(fn ($major) => [
            'label' => $major['name'],
            'href' => $schoolSite . '/jurusan/' . $major['slug'],
        ], $majors)],
        ['label' => 'Berita', 'href' => $schoolSite . '/berita'],
        ['label' => 'BKK', 'active' => request()->is('bkk*'), 'links' => [
            ['label' => 'Beranda BKK', 'href' => $bkkHome . '#beranda', 'section' => 'beranda'],
            ['label' => 'AI CV Enhancer', 'href' => $bkkHome . '#ai-cv', 'section' => 'ai-cv'],
            ['label' => 'Lowongan PKL & Kerja', 'href' => $bkkHome . '#lowongan', 'section' => 'lowongan'],
            ['label' => 'Berita & Agenda BKK', 'href' => $bkkHome . '#berita', 'section' => 'berita'],
            ['label' => 'Tentang BKK', 'href' => route('bkk.tentang'), 'divider' => true],
            ['label' => 'Kerja Sama Mitra IDUKA', 'href' => route('bkk.kerjasama')],
            ['label' => 'Portal Siswa / Masuk', 'href' => $portalUrl],
        ]],
    ];

    $contacts = [
        ['icon' => 'mail', 'label' => 'Email', 'value' => 'bkk@smkpenus.sch.id', 'href' => 'mailto:bkk@smkpenus.sch.id'],
        ['icon' => 'phoneCall', 'label' => 'Telepon', 'value' => '0812-1086-8958 / (021) 875-4321', 'href' => 'tel:081210868958'],
        ['icon' => 'mapPin', 'label' => 'Alamat', 'value' => 'Gg. Olahraga No.20, Ciriung, Kec. Cibinong, Kabupaten Bogor, Jawa Barat 16918', 'href' => null],
    ];

    $socials = [
        ['icon' => 'globe', 'label' => 'Website Resmi', 'href' => $schoolUrl],
        ['icon' => 'instagram', 'label' => 'Instagram', 'href' => 'https://instagram.com/smkpelitanusantara'],
        ['icon' => 'facebook', 'label' => 'Facebook', 'href' => 'https://facebook.com/smkpelitanusantara'],
        ['icon' => 'whatsapp', 'label' => 'WhatsApp', 'href' => $whatsappUrl],
        ['icon' => 'youtube', 'label' => 'YouTube', 'href' => 'https://youtube.com/@smkpelitanusantara'],
    ];

    // TODO: sesuaikan dengan jam layanan sekretariat BKK yang berlaku
    $officeHours = [
        ['day' => 'Senin – Jumat', 'time' => '07.30 – 15.30 WIB'],
        ['day' => 'Sabtu', 'time' => '08.00 – 12.00 WIB'],
        ['day' => 'Minggu & libur nasional', 'time' => 'Tutup'],
    ];

    $footerMenus = [
        'Layanan BKK' => [
            ['label' => 'Eksplorasi Lowongan', 'href' => route('bkk.lowongan')],
            ['label' => 'AI CV Enhancer', 'href' => $bkkHome . '#ai-cv'],
            ['label' => 'Portal Siswa', 'href' => $portalUrl],
            ['label' => 'Registrasi Mitra IDUKA', 'href' => route('bkk.kerjasama')],
        ],
        'Informasi' => [
            ['label' => 'Tentang BKK', 'href' => route('bkk.tentang')],
            ['label' => 'Berita & Agenda', 'href' => route('bkk.berita')],
            ['label' => 'Formulir PPDB Online', 'href' => $ppdbUrl],
            ['label' => 'Profil Sekolah Resmi', 'href' => $schoolUrl],
        ],
    ];

    // KHUSUS TESTING PANITIA LOMBA: hapus array ini & blok "Akses Uji Coba" di footer sebelum production
    $testingLinks = [
        ['label' => 'Login Admin / Siswa', 'href' => config('services.auth_service.login_url', '/login'), 'note' => 'Akun dari auth service'],
        ['label' => 'Dashboard Admin', 'href' => route('bkk.admin.index'), 'note' => 'Role ADMIN, KEPALA_SEKOLAH, TU, DEVELOPER'],
        ['label' => 'Portal Siswa / Alumni', 'href' => route('bkk.me.index'), 'note' => 'Role SISWA, ALUMNI'],
        ['label' => 'Login Mitra IDUKA', 'href' => route('bkk.mitra.login'), 'note' => 'STN / Password123!'],
    ];

    $mapsQuery = 'SMK+Plus+Pelita+Nusantara+Cibinong+Bogor';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BKK Penus - Bursa Kerja Khusus SMK Plus Pelita Nusantara')</title>
    <meta name="description" content="@yield('description', 'Bursa Kerja Khusus SMK Plus Pelita Nusantara: lowongan PKL & kerja terverifikasi IDUKA, dan AI CV Enhancer berstandar ATS.')">
    <link rel="icon" href="{{ asset('images/logosmkpenus.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-sans: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
            --font-display: "Oswald", ui-sans-serif, system-ui, sans-serif;

            --color-brand-darkred: #7A1018;
            --color-brand-deepred: #5C0B12;
            --color-brand-mist: #DDDDDD;
            --color-brand-softmist: #E8E8E8;
            --color-brand-ink: #241012;
            --color-brand-signal: #B72A32;
            --color-brand-warmred: #D04A43;
            --color-brand-rose: #A66B6E;

            --shadow-softpill: 0 8px 30px -8px rgb(36 16 18 / 0.18);

            /* Radius semua kartu & panel: pakai "rounded-card" */
            --radius-card: 1rem;

            --animate-fade-up: fade-up 0.5s ease-out both;
            --animate-scan: scan 1.1s ease-in-out both;

            /* Garis pindai AI CV Studio: turun dari atas ke dasar kartu */
            @keyframes scan {
                from { top: -6rem; opacity: 1; }
                85% { opacity: 1; }
                to { top: 100%; opacity: 0; }
            }

            @keyframes fade-up {
                from { opacity: 0; transform: translateY(16px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }

        @layer base {
            html { scroll-behavior: smooth; }
            body { background-color: #FFFFFF; }
            [x-cloak] { display: none !important; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .animate-fade-up { animation: none; }
        }
    </style>
    @stack('styles')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-brand-ink antialiased bg-white">

{{-- ============================================================
     1. HEADER & NAVIGASI
     Port Navbar.tsx dari FeLandingPageJhic (tampilan & isi sama). Bar penuh di paling atas halaman,
     berubah jadi pill mengambang setelah discroll. Bedanya hanya menu BKK yang di sini berupa dropdown.
     ============================================================ --}}
@php
    // Kelas navbar di paling atas halaman (bar penuh) & setelah discroll (pill mengambang), sama dengan Navbar.tsx
    $navAtTop = 'max-w-full rounded-none bg-white border-transparent border-b-brand-ink/10 px-[max(1.5rem,calc((100%-72rem)/2))] py-3 md:py-4';
    $navFloating = 'max-w-5xl rounded-[2.5rem] bg-brand-softmist border-brand-ink/10 shadow-softpill px-6 md:px-8 py-3 md:py-3.5';
    $chevron = '<path d="m6 9 6 6 6-6"/>';
@endphp
<header
    x-data="bkkNavbar(@js($navSection))"
    class="fixed left-0 right-0 top-0 px-0 z-50 flex justify-center pointer-events-none transition-all duration-300 motion-reduce:transition-none"
    :class="{ 'top-0 px-0': atTop, 'top-6 px-4': !atTop }"
>
    {{-- rounded-[2.5rem] (bukan rounded-full) supaya perubahan sudutnya ikut teranimasi dengan halus.
         :class pakai bentuk objek supaya kelas keadaan awal di atribut class ikut dilepas saat berganti --}}
    <nav
        aria-label="Navigasi Utama"
        class="pointer-events-auto w-full border flex items-center justify-between gap-4 transition-all duration-300 motion-reduce:transition-none {{ $navAtTop }}"
        :class="{ '{{ $navAtTop }}': atTop, '{{ $navFloating }}': !atTop }"
    >
        <a href="{{ $schoolSite }}/" class="flex items-center gap-2.5 sm:gap-3 group">
            <img src="{{ asset('images/logosmkpenus.png') }}" alt="Logo SMK Pelita Nusantara" class="w-9 h-9 sm:w-10 sm:h-10 object-contain drop-shadow-xs group-hover:scale-105 transition-transform shrink-0" loading="eager">
            <div class="flex flex-col">
                <span class="font-display text-base font-bold uppercase tracking-wide text-brand-ink leading-tight group-hover:text-brand-darkred transition-colors">SMK PLUS PELITA NUSANTARA</span>
                <span class="text-[10px] uppercase font-semibold tracking-wider text-brand-darkred mt-0.5">We Are Different</span>
            </div>
        </a>

        <ul class="hidden lg:flex items-center gap-1 xl:gap-1.5">
            @foreach ($navItems as $item)
                @isset($item['links'])
                    @php $groupId = 'nav-' . Str::slug($item['label']); @endphp
                    <li x-data="navDropdown" class="relative" @mouseenter="enter()" @mouseleave="leave()" @focusout="focusOut($event)" @keydown.escape="close(true)" @click.outside="open = false">
                        @if (!empty($item['active']))
                            <button x-ref="button" type="button" @click="toggle()" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="{{ $groupId }}"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors duration-200 text-brand-darkred"
                                :class="{ 'bg-black/5': open }">
                                {{-- Menu aktif ditandai coretan bawah, bukan latar pill --}}
                                <x-sketch.underline size="sm">{{ $item['label'] }}</x-sketch.underline>
                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $chevron !!}</svg>
                            </button>
                        @else
                            <button x-ref="button" type="button" @click="toggle()" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="{{ $groupId }}"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors duration-200 text-brand-ink/80 hover:text-brand-ink hover:bg-black/5"
                                :class="{ 'bg-black/5 text-brand-darkred': open, 'text-brand-ink/80 hover:text-brand-ink hover:bg-black/5': !open }">
                                {{ $item['label'] }}
                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $chevron !!}</svg>
                            </button>
                        @endif

                        {{-- pt-6 jadi "jembatan" supaya hover tidak putus saat kursor turun ke panel --}}
                        <div
                            id="{{ $groupId }}"
                            class="absolute left-1/2 top-full -translate-x-1/2 pt-6 transition-all duration-200 motion-reduce:transition-none invisible opacity-0 -translate-y-1"
                            :class="{ 'visible opacity-100 translate-y-0': open, 'invisible opacity-0 -translate-y-1': !open }"
                        >
                            <ul class="w-max min-w-52 rounded-card border border-brand-ink/10 bg-white p-2 shadow-softpill">
                                @foreach ($item['links'] as $link)
                                    @if (!empty($link['divider']))
                                        <li role="separator" class="mx-2 my-1.5 border-t border-brand-ink/10"></li>
                                    @endif
                                    <li>
                                        @isset($link['section'])
                                            {{-- Section di halaman ini: menu aktif mengikuti posisi scroll --}}
                                            <a href="{{ $link['href'] }}" @click="open = false"
                                                :aria-current="active === '{{ $link['section'] }}' ? 'location' : null"
                                                class="block rounded-lg px-4 py-2.5 text-sm font-medium transition-colors text-brand-ink/80 hover:bg-brand-softmist hover:text-brand-darkred"
                                                :class="{ 'bg-brand-darkred/10 text-brand-darkred': active === '{{ $link['section'] }}', 'text-brand-ink/80 hover:bg-brand-softmist hover:text-brand-darkred': active !== '{{ $link['section'] }}' }">
                                                {{ $link['label'] }}
                                            </a>
                                        @else
                                            <a href="{{ $link['href'] }}" @click="open = false" class="block rounded-lg px-4 py-2.5 text-sm font-medium transition-colors text-brand-ink/80 hover:bg-brand-softmist hover:text-brand-darkred">
                                                {{ $link['label'] }}
                                            </a>
                                        @endisset
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @else
                    <li>
                        <a href="{{ $item['href'] }}" class="block px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors duration-200 text-brand-ink/80 hover:text-brand-ink hover:bg-black/5">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endisset
            @endforeach
        </ul>

        <div class="flex items-center gap-2">
            {{-- Tombol utama: satu-satunya menu yang diberi warna penuh --}}
            <a href="{{ $ppdbUrl }}" class="group hidden sm:inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-5 py-2 text-sm font-semibold text-white shadow-md shadow-brand-darkred/25 transition-shadow hover:shadow-lg hover:shadow-brand-darkred/30">
                Daftar PPDB
                <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-0.5" />
            </a>

            <button type="button" @click="openMenu()" aria-haspopup="dialog" aria-expanded="false" :aria-expanded="menuOpen.toString()" class="lg:hidden p-2 rounded-full text-brand-ink hover:bg-black/5" aria-label="Buka menu">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </nav>

    {{-- Menu mobile pakai <dialog> supaya fokus keyboard tertahan di dalam menu & bisa ditutup dengan Escape --}}
    <dialog
        x-ref="menu"
        @close="onMenuClosed()"
        aria-label="Menu navigasi"
        class="pointer-events-auto size-full max-w-none max-h-none flex-col overscroll-contain bg-brand-darkred/95 backdrop-blur-lg p-6 sm:p-8 text-brand-mist open:flex"
    >
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logosmkpenus.png') }}" alt="" class="w-9 h-9 object-contain drop-shadow shrink-0">
                <div class="flex flex-col">
                    <span class="font-display text-base font-bold uppercase tracking-wide text-white leading-tight">SMK PLUS PELITA NUSANTARA</span>
                    <span class="text-[10px] uppercase font-semibold tracking-wider text-brand-mist/70 mt-0.5">We Are Different</span>
                </div>
            </div>
            <button type="button" @click="closeMenu()" class="p-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white shrink-0" aria-label="Tutup menu">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Rata atas (bukan di tengah) supaya menu tidak bergeser saat grup dibuka --}}
        <ul class="flex flex-col gap-5 py-10 animate-fade-up">
            @foreach ($navItems as $item)
                @isset($item['links'])
                    @php
                        $groupId = 'mobile-nav-' . Str::slug($item['label']);
                        $groupActive = !empty($item['active']);
                    @endphp
                    {{-- Grup yang berisi halaman sekarang langsung terbuka --}}
                    <li x-data="{ open: @js($groupActive) }">
                        <button type="button" @click="open = !open" aria-expanded="{{ $groupActive ? 'true' : 'false' }}" :aria-expanded="open.toString()" aria-controls="{{ $groupId }}"
                            class="flex w-full items-center justify-between gap-4 font-display text-3xl font-bold uppercase tracking-wide transition-colors {{ $groupActive ? 'text-white' : 'text-brand-mist/80 hover:text-white' }}">
                            {{ $item['label'] }}
                            <svg class="w-6 h-6 shrink-0 transition-transform duration-300 {{ $groupActive ? 'rotate-180' : '' }}" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $chevron !!}</svg>
                        </button>

                        {{-- Trik grid-rows 0fr -> 1fr supaya tinggi panel bisa dianimasikan --}}
                        <div id="{{ $groupId }}"
                            class="grid transition-all duration-300 motion-reduce:transition-none {{ $groupActive ? 'visible grid-rows-[1fr] opacity-100' : 'invisible grid-rows-[0fr] opacity-0' }}"
                            :class="{ 'visible grid-rows-[1fr] opacity-100': open, 'invisible grid-rows-[0fr] opacity-0': !open }">
                            <div class="overflow-hidden">
                                <ul class="mt-4 flex flex-col border-l border-white/15">
                                    @foreach ($item['links'] as $link)
                                        <li>
                                            @isset($link['section'])
                                                <a href="{{ $link['href'] }}" @click="closeMenu()"
                                                    :aria-current="active === '{{ $link['section'] }}' ? 'location' : null"
                                                    class="-ml-px block border-l-2 py-1.5 pl-5 text-lg font-medium transition-colors border-transparent text-brand-mist/80 hover:text-white"
                                                    :class="{ 'border-brand-warmred text-white': active === '{{ $link['section'] }}', 'border-transparent text-brand-mist/80 hover:text-white': active !== '{{ $link['section'] }}' }">
                                                    {{ $link['label'] }}
                                                </a>
                                            @else
                                                <a href="{{ $link['href'] }}" @click="closeMenu()" class="-ml-px block border-l-2 border-transparent py-1.5 pl-5 text-lg font-medium text-brand-mist/80 transition-colors hover:text-white">
                                                    {{ $link['label'] }}
                                                </a>
                                            @endisset
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @else
                    <li>
                        <a href="{{ $item['href'] }}" @click="closeMenu()" class="font-display text-3xl font-bold uppercase tracking-wide transition-colors text-brand-mist/80 hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endisset
            @endforeach
        </ul>

        <div class="mt-auto border-t border-white/15 pt-6">
            <a href="{{ $ppdbUrl }}" class="group flex items-center justify-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-semibold text-brand-darkred transition-colors hover:bg-brand-mist">
                Daftar PPDB
                <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-0.5" />
            </a>
        </div>
    </dialog>
</header>

<main>
    @yield('content')
</main>

{{-- ============================================================
     9. FOOTER
     ============================================================ --}}
<footer class="relative bg-white pt-4 text-left">
    {{-- Banner ajakan: teks di kiri, foto siswa di kanan (di HP & tablet di bawah teks) --}}
    <div class="px-6 mb-14">
        <div class="relative max-w-6xl mx-auto overflow-hidden rounded-4xl border border-white/10 bg-linear-135 from-brand-darkred to-brand-deepred p-8 sm:p-12 lg:p-14 text-white shadow-softpill">
            <div class="relative grid gap-8 lg:grid-cols-2 lg:gap-6">
                <div class="lg:self-center">
                    <p class="mb-3 text-xs sm:text-sm font-bold uppercase tracking-[0.25em] text-[#F5C2C7]">
                        Siap Masuk Dunia <x-sketch.sparks tone="text-[#F5C2C7]">Industri?</x-sketch.sparks>
                    </p>
                    <h2 class="font-display text-xl sm:text-2xl md:text-3xl lg:text-[2rem] font-bold uppercase tracking-wide leading-snug text-white text-left">
                        Optimalkan CV, Pilih Lowongan Terverifikasi, dan Melangkah Bersama Ribuan Alumni Penus.
                    </h2>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ $portalUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-7 py-3 text-sm font-bold text-brand-darkred transition-colors hover:bg-brand-mist active:scale-95">
                            <x-landing.icon name="login" class="w-4 h-4" />
                            Masuk Portal Siswa
                        </a>
                        <a href="{{ route('bkk.kerjasama') }}" class="group inline-flex items-center justify-center gap-2 rounded-full border-2 border-white/30 px-7 py-3 text-sm font-semibold text-white transition-all hover:border-white hover:bg-white/10 active:scale-95">
                            Daftarkan Perusahaan
                            <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                        </a>
                    </div>
                </div>
                {{-- Margin negatif = padding banner, supaya foto menempel ke tepi bawah banner --}}
                <div class="relative self-end -mx-8 -mb-8 sm:-mx-12 sm:-mb-12 lg:ml-0 lg:-mr-14 lg:-mb-14">
                    <img src="{{ asset('images/TalentaVokasi.png') }}" alt="Siswa SMK Plus Pelita Nusantara dari berbagai jurusan" class="relative w-full aspect-1326/588 object-cover object-bottom lg:mask-[linear-gradient(to_right,transparent,black_12%)]" loading="lazy">
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-brand-ink/10 px-6 pt-16 pb-12 text-brand-ink">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 items-start">
            {{-- Identitas, kontak, media sosial, logo mitra --}}
            <div class="lg:col-span-4 space-y-4">
                <a href="{{ url('/bkk') }}" class="group flex w-fit items-center gap-3">
                    <img src="{{ asset('images/logosmkpenus.png') }}" alt="" class="h-12 w-12 shrink-0 object-contain transition-transform group-hover:scale-105" loading="lazy">
                    <span class="flex flex-col">
                        <span class="font-display text-base font-bold uppercase tracking-wide leading-tight text-brand-ink transition-colors group-hover:text-brand-darkred">SMK Plus Pelita Nusantara</span>
                        <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-brand-darkred">Bursa Kerja Khusus</span>
                    </span>
                </a>
                <p class="max-w-sm text-xs sm:text-[13px] leading-relaxed text-brand-ink/75">
                    Lembaga penyalur kerja dan PKL resmi SMK Plus Pelita Nusantara. Mempersiapkan lulusan vokasi yang kompeten dan siap terserap di dunia industri.
                </p>

                <address class="space-y-2.5 pt-1 text-xs not-italic text-brand-ink/80">
                    @foreach ($contacts as $contact)
                        <div class="flex items-start gap-2.5">
                            <x-landing.icon :name="$contact['icon']" class="w-4 h-4 shrink-0 text-brand-signal" />
                            @if ($contact['href'])
                                <a href="{{ $contact['href'] }}" class="transition-colors hover:text-brand-darkred hover:underline"><span class="sr-only">{{ $contact['label'] }}: </span>{{ $contact['value'] }}</a>
                            @else
                                <p class="leading-snug"><span class="sr-only">{{ $contact['label'] }}: </span>{{ $contact['value'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </address>

                <ul class="flex items-center gap-4 pt-2" aria-label="Media sosial">
                    @foreach ($socials as $social)
                        <li>
                            <a href="{{ $social['href'] }}" target="_blank" rel="noreferrer" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}" class="block text-brand-signal transition-colors hover:text-brand-darkred">
                                <x-landing.icon :name="$social['icon']" class="w-4 h-4" />
                            </a>
                        </li>
                    @endforeach
                </ul>

                <img src="{{ asset('images/logofooter.webp') }}" alt="Partner & kolaborator: Jagoan Hosting Infra Competition, Jagoan Hosting, Komdigi, Maspion IT, Garuda Spark Innovation Hub" width="3727" height="592" class="h-auto w-full max-w-xs opacity-90 transition-opacity hover:opacity-100" loading="lazy">
            </div>

            @foreach ($footerMenus as $title => $links)
                <div class="lg:col-span-2">
                    <h2 class="font-display text-sm sm:text-base font-bold uppercase tracking-wide text-brand-ink">{{ $title }}</h2>
                    <ul class="mt-3.5 space-y-2 text-xs sm:text-[13px] font-medium text-brand-ink/75">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['href'] }}" class="transition-colors hover:text-brand-darkred">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="lg:col-span-4 space-y-6">
                <div>
                    <h2 class="font-display text-sm sm:text-base font-bold uppercase tracking-wide text-brand-ink">Jam Operasional Sekretariat</h2>
                    <dl class="mt-3.5 space-y-1.5 text-xs sm:text-[13px] text-brand-ink/75">
                        @foreach ($officeHours as $hour)
                            <div class="flex justify-between gap-4 border-b border-dashed border-brand-ink/10 pb-1.5">
                                <dt>{{ $hour['day'] }}</dt>
                                <dd class="font-medium text-brand-ink">{{ $hour['time'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div>
                    <h2 class="font-display text-sm sm:text-base font-bold uppercase tracking-wide text-brand-ink">Lokasi Sekolah</h2>
                    <div class="relative mt-3 h-44 w-full overflow-hidden rounded-card border border-brand-ink/15 shadow-sm">
                        <a href="https://maps.google.com/?q={{ $mapsQuery }}" target="_blank" rel="noreferrer" class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-sm transition-all hover:bg-slate-50 hover:shadow-md">
                            Buka di Maps
                            <x-landing.icon name="externalLink" class="w-3.5 h-3.5" />
                        </a>
                        <iframe title="Peta Lokasi SMK Plus Pelita Nusantara" src="https://maps.google.com/maps?q={{ $mapsQuery }}&t=&z=15&ie=UTF8&iwloc=&output=embed" class="h-full w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <p class="mt-3 text-[11px] leading-tight text-brand-ink/60">Ciriung, Cibinong, dekat pusat pemerintahan Kabupaten Bogor.</p>
                </div>
            </div>
        </div>

        {{-- KHUSUS TESTING PANITIA LOMBA: hapus blok ini sebelum production --}}
        <div class="max-w-6xl mx-auto mt-12 rounded-card border border-dashed border-brand-signal/40 bg-brand-signal/5 p-5">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-display text-sm sm:text-base font-bold uppercase tracking-wide text-brand-ink">Akses Uji Coba Panitia</h2>
                <span class="rounded-full bg-brand-signal px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Testing</span>
            </div>
            <p class="mt-1.5 text-[11px] sm:text-xs leading-relaxed text-brand-ink/70">
                Link di bawah ini hanya untuk keperluan penilaian &amp; uji coba oleh panitia lomba, bukan akses resmi, dan akan dihapus saat production.
            </p>
            <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ($testingLinks as $link)
                    <li>
                        <a href="{{ $link['href'] }}" class="group block h-full rounded-lg border border-brand-ink/10 bg-white px-3.5 py-2.5 transition-colors hover:border-brand-darkred">
                            <span class="block text-xs sm:text-[13px] font-semibold text-brand-ink group-hover:text-brand-darkred">{{ $link['label'] }}</span>
                            <span class="mt-0.5 block text-[11px] text-brand-ink/60">{{ $link['note'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="max-w-6xl mx-auto mt-12 flex flex-col gap-3 border-t border-brand-ink/10 pt-6 text-[11px] sm:text-xs font-medium text-brand-ink/60 sm:flex-row sm:items-center sm:justify-between">
            <p>Copyright &copy; {{ date('Y') }} BKK SMK Plus Pelita Nusantara. All right reserved | PENUS</p>
            <p>Terakreditasi A BAN-PDM · Link &amp; Match Vokasi</p>
        </div>
    </div>
</footer>

{{-- ============================================================
     Komponen Alpine. Didaftarkan di alpine:init; skrip ini jalan sebelum Alpine (defer) dimulai.
     ============================================================ --}}
<script>
    document.addEventListener('alpine:init', () => {
        // Navbar: bar penuh di atas halaman -> pill mengambang setelah discroll, menu mobile <dialog>, penanda section aktif
        Alpine.data('bkkNavbar', (initialSection = 'beranda') => ({
            atTop: window.scrollY < 24,
            menuOpen: false,
            active: initialSection,

            init() {
                const onScroll = () => { this.atTop = window.scrollY < 24; };
                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });

                // Section yang melewati garis tengah layar dianggap aktif
                const sections = ['beranda', 'ai-cv', 'lowongan', 'berita']
                    .map((id) => document.getElementById(id))
                    .filter(Boolean);
                const spy = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) this.active = entry.target.id;
                    });
                }, { rootMargin: '-45% 0px -50% 0px' });
                sections.forEach((section) => spy.observe(section));

                // Tutup sendiri kalau layar dilebarkan sampai ukuran desktop
                const desktop = window.matchMedia('(min-width: 64rem)');
                desktop.addEventListener('change', () => desktop.matches && this.closeMenu());
            },

            openMenu() {
                this.menuOpen = true;
                this.$refs.menu.showModal();
                document.body.style.overflow = 'hidden';
            },

            closeMenu() {
                if (this.$refs.menu.open) this.$refs.menu.close();
            },

            // Dipanggil event "close" dialog: tombol tutup, Escape, atau klik link
            onMenuClosed() {
                this.menuOpen = false;
                document.body.style.overflow = '';
            },
        }));

        // Dropdown menu desktop: buka saat hover atau klik, tutup saat klik di luar, Escape, atau fokus keluar (Tab)
        Alpine.data('navDropdown', () => ({
            open: false,
            // Kalau sudah terbuka karena hover, klik pertama di tombolnya jangan malah menutup menu
            openedByHover: false,

            enter() {
                this.openedByHover = true;
                this.open = true;
            },

            leave() {
                this.openedByHover = false;
                this.open = false;
            },

            toggle() {
                if (this.openedByHover) {
                    this.openedByHover = false;
                    this.open = true;
                } else {
                    this.open = !this.open;
                }
            },

            close(focusButton = false) {
                if (!this.open) return;
                this.open = false;
                if (focusButton) this.$refs.button.focus();
            },

            focusOut(event) {
                if (event.relatedTarget && !this.$root.contains(event.relatedTarget)) this.open = false;
            },
        }));

        // Accordion FAQ (dipakai x-landing.faq): satu jawaban terbuka, pertanyaan pertama terbuka dari awal
        Alpine.data('faq', () => ({
            open: 0,

            toggle(index) {
                this.open = this.open === index ? null : index;
            },

            // Panah atas/bawah pindah antar pertanyaan (berputar), Home/End ke ujung
            keys(event) {
                const buttons = Array.from(this.$root.querySelectorAll('h3 > button'));
                const current = buttons.indexOf(event.target);
                if (current === -1) return;

                const targets = { ArrowDown: current + 1, ArrowUp: current - 1, Home: 0, End: buttons.length - 1 };
                const next = targets[event.key];
                if (next === undefined) return;

                event.preventDefault();
                buttons[(next + buttons.length) % buttons.length]?.focus();
            },
        }));

        // Carousel sederhana: geser otomatis, berhenti saat hover/fokus, tidak otomatis kalau pengguna memilih kurangi animasi
        Alpine.data('carousel', ({ count, interval = 6000 }) => ({
            current: 0,
            count,
            timer: null,

            init() {
                this.resume();
            },

            go(index) {
                this.current = (index + this.count) % this.count;
            },

            next() {
                this.go(this.current + 1);
            },

            prev() {
                this.go(this.current - 1);
            },

            pause() {
                clearInterval(this.timer);
                this.timer = null;
            },

            resume() {
                if (this.timer || this.count < 2) return;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                this.timer = setInterval(() => this.next(), interval);
            },

            destroy() {
                this.pause();
            },
        }));

        // Tombol salin tautan dengan cadangan untuk browser tanpa Clipboard API (mis. halaman http)
        Alpine.data('copyLink', (url) => ({
            copied: false,

            async copy() {
                try {
                    await navigator.clipboard.writeText(url);
                } catch {
                    const field = document.createElement('textarea');
                    field.value = url;
                    field.setAttribute('readonly', '');
                    field.style.position = 'fixed';
                    field.style.opacity = '0';
                    document.body.appendChild(field);
                    field.select();
                    document.execCommand('copy');
                    field.remove();
                }
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            },
        }));

        // AI CV Studio: sakelar Mode AI antara CV asli dan hasil saran AI, garis pindai lewat setiap kali dinyalakan
        Alpine.data('cvStudio', () => ({
            ai: true,
            scanning: false,
            timer: null,

            toggle() {
                this.ai = !this.ai;
                clearTimeout(this.timer);
                this.scanning = false;
                if (!this.ai || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                // Template x-if dibuat ulang supaya animasinya selalu mulai dari atas
                this.$nextTick(() => {
                    this.scanning = true;
                    this.timer = setTimeout(() => { this.scanning = false; }, 1100);
                });
            },

            destroy() {
                clearTimeout(this.timer);
            },
        }));

        // Filter lowongan langsung di halaman. jobs = [{ tipe, jurusan: [kode], text }] dari server
        Alpine.data('jobFilter', (jobs) => ({
            jobs,
            q: '',
            tipe: '',
            jurusan: 'all',

            isVisible(index) {
                const job = this.jobs[index];
                const words = this.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
                return (!this.tipe || job.tipe === this.tipe)
                    && (this.jurusan === 'all' || job.jurusan.includes(this.jurusan))
                    && words.every((word) => job.text.includes(word));
            },

            get visibleCount() {
                return this.jobs.filter((job, index) => this.isVisible(index)).length;
            },

            reset() {
                this.q = '';
                this.tipe = '';
                this.jurusan = 'all';
            },
        }));

        // Skor ATS di kartu hero naik dari 0 ke target
        Alpine.data('atsGauge', (target) => ({
            score: 0,

            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.score = target;
                    return;
                }
                const duration = 1400;
                const start = performance.now() + 400;
                const tick = (now) => {
                    const t = Math.min(Math.max((now - start) / duration, 0), 1);
                    this.score = Math.round(target * (1 - Math.pow(1 - t, 3)));
                    if (t < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
            },
        }));
    });
</script>

@include('partials.sketch-engine')

@stack('scripts')
</body>
</html>
