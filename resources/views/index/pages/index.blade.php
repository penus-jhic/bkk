{{--
    Beranda BKK SMK Plus Pelita Nusantara.
    Memakai index.layouts.landing (Tailwind v4, navbar, footer & mesin coretan data-sketch), bukan index.master,
    karena komponen x-landing.* & x-sketch.* ditulis untuk Tailwind v4 sedangkan index.master masih Tailwind v3 CDN.
    Data dari PublicController@index: $featuredLowongan, $latestBerita, $mitraCount. PublicController@info memakai
    view yang sama tanpa data, jadi semuanya diberi nilai cadangan di bawah.
--}}
@php
    $featuredLowongan = $featuredLowongan ?? collect();
    $latestBerita = $latestBerita ?? collect();
    $mitraCount = (int) ($mitraCount ?? 0);

    $whatsappUrl = 'https://wa.me/6281210868958';
    $cvStudioUrl = route('bkk.me.cv.edit');
    $portalUrl = route('bkk.me.index');

    $jurusanFilters = ['RPL' => 'RPL', 'TKJ' => 'TKJ', 'MM' => 'Multimedia', 'PKM' => 'Perbankan', 'TOI' => 'Otomasi'];

    // target_jurusan berupa teks bebas ("TKJ & RPL", "Desain Komunikasi Visual (DKV)"), jadi dicocokkan ke kode filter
    $detectJurusan = function (?string $text): array {
        $patterns = [
            'RPL' => '/\bRPL\b|perangkat lunak/i',
            'TKJ' => '/\bTKJ\b|komputer\s*(dan|&)?\s*jaringan/i',
            'MM' => '/multimedia|\bMM\b|\bDKV\b|desain komunikasi/i',
            'PKM' => '/perbankan|keuangan|akuntansi|\bPKM\b/i',
            'TOI' => '/otomasi|\bTOI\b|mekatronika/i',
        ];

        return array_keys(array_filter($patterns, fn ($pattern) => preg_match($pattern, $text ?? '')));
    };

    // Label tenggat sama dengan x-landing.lowongan-card
    $deadlineInfo = function ($deadline): array {
        $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false) : null;

        return match (true) {
            $daysLeft === null => ['Tanpa batas waktu', false],
            $daysLeft < 0 => ['Pendaftaran ditutup', false],
            $daysLeft === 0 => ['Hari terakhir', true],
            $daysLeft <= 7 => [$daysLeft . ' hari lagi', true],
            default => ['Tutup ' . $deadline->locale('id')->translatedFormat('j M Y'), false],
        };
    };

    $jobs = $featuredLowongan->map(fn ($lowongan) => [
        'title' => $lowongan->judul,
        'mitra' => $lowongan->mitra,
        'company' => $lowongan->mitra->nama_perusahaan ?? 'Mitra Industri BKK',
        'pkl' => $lowongan->tipe === 'PKL',
        'jurusan' => $lowongan->target_jurusan,
        'lokasi' => $lowongan->lokasi,
        'gaji' => $lowongan->gaji_kompensasi,
        'deadline' => $lowongan->deadline,
        'url' => route('bkk.lowongan.detail', $lowongan->slug ?: $lowongan->id),
    ])->all();

    // Data ringkas untuk filter Alpine (pencarian, kategori, jurusan) di section lowongan
    $jobFilterData = array_map(fn ($job) => [
        'tipe' => $job['pkl'] ? 'pkl' : 'kerja',
        'jurusan' => $detectJurusan($job['jurusan']),
        'text' => mb_strtolower(implode(' ', [$job['title'], $job['company'], $job['jurusan'], $job['lokasi']])),
    ], $jobs);

    $news = $latestBerita->all();

    $pillars = [
        ['icon' => 'shield', 'label' => 'Lowongan Mitra IDUKA Terverifikasi'],
        ['icon' => 'bulb', 'label' => 'AI CV Optimizer Berstandar ATS'],
        ['icon' => 'users', 'label' => 'Jejaring & Mentoring 1.500+ Alumni'],
    ];

    // Dokumen CV contoh di AI CV Studio: before = CV asli (+ masalahnya), after = hasil saran AI (+ jenis perbaikan)
    $cvDocRows = [
        ['label' => 'Kontak', 'before' => 'Ditulis di tabel dua kolom bersama foto dan ikon media sosial.', 'issue' => 'Gagal dibaca parser', 'after' => 'nurul.aisyah@email.com · 0812-1234-5678 · Bogor · github.com/nurulaisyah', 'fix' => 'Struktur kontak standar ATS'],
        ['label' => 'Ringkasan', 'before' => 'Saya orang yang rajin, jujur, dan mau belajar hal baru.', 'issue' => 'Terlalu umum', 'after' => 'Lulusan RPL dengan pengalaman PKL 6 bulan membangun aplikasi web Laravel dan REST API untuk kebutuhan internal perusahaan.', 'fix' => 'Kata kunci lowongan'],
        ['label' => 'Pengalaman', 'before' => 'Membuat website sekolah pakai Laravel.', 'issue' => 'Tanpa angka capaian', 'after' => 'Membangun sistem informasi PKL berbasis Laravel 11 untuk 300+ siswa, memangkas waktu rekap jurnal harian hingga 40%.', 'fix' => 'Metrik kuantitatif'],
        ['label' => 'Sertifikasi', 'before' => 'Belum dicantumkan.', 'issue' => 'Sertifikat terlewat', 'after' => 'Sertifikat Kompetensi BNSP — Pemrogram Junior, LSP P1 SMK Plus Pelita Nusantara (2024)', 'fix' => 'Diambil dari data LSP'],
    ];
    $cvSkills = [
        'before' => ['Komputer', 'Internet', 'Ms Office'],
        'after' => ['Laravel', 'REST API', 'MySQL', 'Git', 'Tailwind CSS'],
    ];

    // Tebalkan kata kunci industri di teks hasil AI. Teks di-escape dulu, baru dibungkus <mark>
    $highlightKeywords = fn (string $text): string => preg_replace(
        '/(Laravel 11|Laravel|REST API|PKL 6 bulan|300\+ siswa|40%|BNSP)/',
        '<mark class="rounded-sm bg-brand-warmred/15 font-semibold text-brand-darkred">$1</mark>',
        e($text),
    );

    $cvParsedSections = ['Nama & Kontak', 'Ringkasan', 'Pendidikan', 'Pengalaman PKL', 'Keahlian', 'Sertifikasi'];

    // Kata kunci yang sering muncul di kualifikasi lowongan mitra, per jurusan (kunci = kode di $jurusanFilters)
    $cvKeywords = [
        'RPL' => ['Laravel', 'REST API', 'MySQL', 'Git', 'Tailwind CSS', 'Unit Testing'],
        'TKJ' => ['MikroTik', 'Fiber Optic', 'Linux Server', 'Troubleshooting', 'Cisco CCNA', 'Helpdesk'],
        'MM' => ['Adobe Premiere', 'Figma', 'Motion Graphic', 'Fotografi Produk', 'Content Planning', 'Canva'],
        'PKM' => ['Pelayanan Prima', 'Teller', 'Akuntansi Dasar', 'Ms Excel', 'Literasi Keuangan', 'Customer Service'],
        'TOI' => ['PLC', 'Wiring Panel', 'Sensor & Aktuator', 'Pneumatik', 'HMI', 'K3 Industri'],
    ];

    $cvSources = [
        ['icon' => 'book', 'label' => 'Jurnal PKL'],
        ['icon' => 'award', 'label' => 'Sertifikat LSP'],
        ['icon' => 'pen', 'label' => 'Tugas Praktik'],
    ];

    $steps = [
        ['no' => '01', 'title' => 'Lengkapi Profil & Scan CV (AI)', 'desc' => 'Isi data diri, unggah sertifikat, lalu biarkan AI menilai CV-mu terhadap standar ATS.', 'icon' => 'user'],
        ['no' => '02', 'title' => 'Pilih Lowongan Sesuai Rekomendasi', 'desc' => 'Sistem menyarankan lowongan PKL & kerja yang paling cocok dengan jurusan dan skormu.', 'icon' => 'search'],
        ['no' => '03', 'title' => 'Kirim Lamaran 1-Klik', 'desc' => 'CV hasil optimasi langsung terkirim ke mitra IDUKA, statusnya bisa dipantau dari Portal Siswa.', 'icon' => 'send'],
        ['no' => '04', 'title' => 'Pendampingan Wawancara & Seleksi', 'desc' => 'Guru BKK dan alumni mentor mendampingi simulasi wawancara sampai kamu diterima.', 'icon' => 'handshake'],
    ];

    $faqs = [
        [
            'q' => 'Siapa saja yang bisa memakai layanan BKK Penus?',
            'a' => 'Seluruh siswa aktif SMK Plus Pelita Nusantara untuk program PKL, serta alumni untuk lowongan kerja dari mitra IDUKA. Cukup masuk ke Portal Siswa memakai akun sekolah.',
            'link' => ['label' => 'Masuk Portal Siswa', 'href' => $portalUrl],
        ],
        [
            'q' => 'Apakah melamar lewat BKK dipungut biaya?',
            'a' => 'Tidak. Semua layanan BKK, mulai dari pendaftaran lowongan, AI CV Enhancer, hingga pendampingan wawancara, gratis untuk siswa dan alumni. Laporkan ke sekretariat BKK bila ada pihak yang meminta bayaran atas nama sekolah.',
        ],
        [
            'q' => 'Bagaimana cara kerja AI CV Enhancer?',
            'a' => 'AI membaca CV-mu seperti sistem ATS milik HRD, memberi skor kesiapan, lalu menyarankan perbaikan format, kata kunci industri, dan poin pencapaian dari jurnal PKL serta sertifikat LSP. Kamu tetap memutuskan saran mana yang dipakai.',
            'link' => ['label' => 'Buka AI CV Studio', 'href' => $cvStudioUrl],
        ],
        [
            'q' => 'Bagaimana proses penempatan PKL melalui BKK?',
            'a' => 'Pilih lowongan PKL yang sesuai jurusan, kirim lamaran dari Portal Siswa, lalu ikuti seleksi dari perusahaan. Setelah diterima, jurnal harian dan laporan PKL dikerjakan dan divalidasi langsung di portal.',
            'link' => ['label' => 'Lihat Lowongan PKL', 'href' => route('bkk.lowongan', ['tipe' => 'pkl'])],
        ],
        [
            'q' => 'Perusahaan kami ingin merekrut lulusan. Bagaimana caranya?',
            'a' => 'Ajukan permohonan kerja sama melalui halaman Kerja Sama Mitra. Setelah diverifikasi tim BKK, perusahaan mendapat akses dasbor mitra untuk memasang lowongan dan meninjau CV pelamar.',
            'link' => ['label' => 'Ajukan Kerja Sama', 'href' => route('bkk.kerjasama')],
        ],
    ];

@endphp

@extends('index.layouts.landing')

@section('content')
{{-- ============================================================
     2. HERO
     ============================================================ --}}
<section id="beranda" class="relative overflow-hidden bg-white pt-28 md:pt-36 pb-16 md:pb-24 px-6 scroll-mt-24">
    {{-- Latar: grid halus + semburat merah --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-size-[44px_44px] mask-[radial-gradient(ellipse_70%_60%_at_30%_30%,#000_60%,transparent_100%)]"></div>
        <div class="absolute -top-40 -right-32 w-140 h-140 rounded-full bg-brand-signal/10 blur-[120px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto grid gap-14 lg:grid-cols-[1.1fr_1fr] lg:gap-12 items-center">
        <div class="animate-fade-up">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                Bursa Kerja Khusus &amp; Pusat Karier Digital Vokasi
            </p>
            <h1 class="mt-5 font-display text-4xl sm:text-6xl lg:text-7xl font-bold uppercase tracking-wide leading-[1.05] text-left">
                Langsung Terhubung ke
                <x-sketch.underline size="lg">Industri,</x-sketch.underline>
                <span class="block mt-2 sm:mt-3 text-brand-darkred">Dibekali Teknologi AI.</span>
            </h1>
            <p class="mt-8 max-w-xl text-base md:text-lg leading-relaxed text-brand-ink/70">
                Satu portal untuk mencari lowongan PKL &amp; kerja dari mitra IDUKA terverifikasi, mengoptimalkan CV agar lolos
                penyaringan ATS, dan terhubung dengan alumni yang sudah berkarier di perusahaan terkemuka.
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-4">
                <a href="#lowongan" class="group inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    Cari Lowongan Aktif
                    <x-sketch.arrow :delay="600" class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                </a>
                <a href="#ai-cv" class="group inline-flex items-center gap-2 rounded-full border-2 border-brand-darkred/20 bg-white px-7 py-3 text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                    <x-landing.icon name="bulb" class="w-4 h-4" />
                    <span class="mr-4">Tingkatkan CV dengan AI</span>
                </a>
            </div>
        </div>

        {{-- Visual hero: foto gedung + kartu analisis CV interaktif --}}
        <div class="relative lg:pl-6">
            <div class="relative overflow-hidden rounded-card shadow-softpill ring-1 ring-brand-ink/5">
                <img src="{{ asset('images/fotogedung.jpg') }}" alt="Gedung SMK Plus Pelita Nusantara di Cibinong, Bogor" class="w-full aspect-4/5 sm:aspect-4/3 lg:aspect-4/5 object-cover" loading="eager">
                {{-- Keterangan di atas foto, karena bagian bawahnya tertutup kartu analisis CV di HP & tablet --}}
                <div class="absolute inset-0 bg-linear-to-b from-brand-ink/75 via-brand-ink/5 to-transparent"></div>
                <div class="absolute left-5 top-5 right-5 text-white">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">SMK PLUS PELITA NUSANTARA Cibinong, Bogor</p>
                    <p class="mt-1 font-display text-2xl font-bold uppercase tracking-wide">We Are Different</p>
                </div>
            </div>
            {{-- Siku coretan di luar foto, berjarak 1rem dari tepinya supaya goresannya tidak menimpa foto.
                 Kiri: di desktop foto bergeser lg:pl-6 (1.5rem), jadi sikunya cukup lg:left-2 agar jaraknya tetap 1rem.
                 Kanan: foto menempel ke tepi kanan wadah, jadi sikunya keluar -right-4 di semua ukuran. --}}
            <x-sketch.corner class="-left-4 -top-4 w-24 h-10 md:w-32 md:h-12 lg:left-2" />
            <x-sketch.corner :delay="350" class="-right-4 -bottom-4 rotate-180 w-24 h-10 md:w-32 md:h-12" />
        </div>
    </div>

    {{-- Statistik singkat --}}
    <dl class="relative max-w-6xl mx-auto mt-16 md:mt-20 grid grid-cols-2 lg:grid-cols-4 gap-px overflow-hidden rounded-card bg-brand-ink/10 ring-1 ring-brand-ink/10">
        @php
            $stats = array_values(array_filter([
                $mitraCount > 0 ? ['value' => $mitraCount, 'label' => 'Mitra IDUKA terverifikasi'] : null,
                ['value' => '1.500+', 'label' => 'Alumni dalam jejaring'],
                ['value' => '92%', 'label' => 'Lulusan terserap kerja & studi'],
                ['value' => 'A', 'label' => 'Akreditasi BAN-PDM 2024–2029'],
                $mitraCount > 0 ? null : ['value' => '5', 'label' => 'Kompetensi keahlian'],
            ]));
        @endphp
        @foreach ($stats as $stat)
            <div class="flex flex-col bg-white px-5 py-6 md:px-7">
                <dt class="text-xs sm:text-sm text-brand-ink/60">{{ $stat['label'] }}</dt>
                <dd class="order-first font-display text-3xl md:text-4xl font-bold uppercase tracking-wide text-brand-darkred">{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>
</section>

{{-- ============================================================
     3. AI CV ENHANCER & ATS OPTIMIZER
     ============================================================ --}}
<section id="ai-cv" class="relative bg-brand-softmist px-6 py-20 md:py-28 scroll-mt-24">
    <div class="max-w-6xl mx-auto">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                <x-sketch.sparks>AI CV Enhancer</x-sketch.sparks>
            </p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight text-left">
                <x-sketch.frame class="-ml-4 md:-ml-5">Buat &amp; Optimalkan CV Digital Siap Kerja Berstandar ATS</x-sketch.frame>
            </h2>
            <p class="mt-5 text-base md:text-lg leading-relaxed text-brand-ink/70">
                Sebagian besar HRD menyaring lamaran dengan Applicant Tracking System. AI CV Studio membantu siswa dan alumni
                menyusun CV yang terbaca mesin sekaligus meyakinkan perekrut.
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-12 items-start">
            {{-- Studio: dokumen CV contoh dengan sakelar Mode AI (nyala = hasil saran AI, mati = CV asli).
                 Tabel bergaya coretan tangan seperti tabel identitas di FeLandingPageJhic: bingkai x-sketch.box,
                 garis baris & kolom x-sketch.rule. Tanpa overflow-hidden supaya ujung coretan (±8px) tidak terpotong. --}}
            <div x-data="cvStudio" class="relative lg:col-span-7 rounded-sm bg-white text-left">
                <x-sketch.box />

                {{-- Bilah atas bergaya jendela aplikasi --}}
                <div class="relative flex flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-7">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex shrink-0 gap-1.5" aria-hidden="true">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-darkred"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-darkred/30"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-darkred/15"></span>
                        </span>
                        <p class="truncate font-display text-base font-bold uppercase tracking-wide">AI CV Studio <span class="font-sans text-sm font-normal normal-case tracking-normal text-brand-ink/50">· CV_Nurul_RPL.pdf</span></p>
                    </div>
                    <button type="button" role="switch" aria-checked="true" :aria-checked="ai.toString()" @click="toggle()" class="inline-flex items-center gap-3 rounded-full text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-darkred">
                        Mode AI
                        <span class="relative h-6 w-11 rounded-full transition-colors bg-brand-darkred" :class="{ 'bg-brand-darkred': ai, 'bg-brand-ink/20': !ai }" aria-hidden="true">
                            <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow transition-transform motion-reduce:transition-none translate-x-5" :class="{ 'translate-x-5': ai, 'translate-x-0': !ai }"></span>
                        </span>
                    </button>
                    <x-sketch.rule class="text-brand-darkred/30 -left-1 -right-1 -bottom-1.5 h-3" />
                </div>

                {{-- overflow-hidden di sini saja, supaya garis pindai tidak keluar dari area dokumen --}}
                <div class="relative overflow-hidden px-5 pb-2 pt-6 md:px-7">
                    {{-- Garis pindai, lewat sekali setiap Mode AI dinyalakan --}}
                    <template x-if="scanning">
                        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 z-10 h-24 border-b-2 border-brand-warmred/60 bg-linear-to-b from-transparent via-brand-warmred/10 to-brand-warmred/20 animate-scan motion-reduce:hidden"></div>
                    </template>

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-display text-2xl font-bold uppercase tracking-wide">Nurul Aisyah</p>
                            <p class="text-sm text-brand-ink/60">Junior Web Developer · Rekayasa Perangkat Lunak</p>
                        </div>
                        <div class="shrink-0 text-right" aria-live="polite">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-ink/50">Skor ATS</p>
                            <p class="font-display text-4xl font-bold leading-none transition-colors text-brand-darkred" :class="{ 'text-brand-darkred': ai, 'text-brand-ink/35': !ai }">
                                <span x-text="ai ? 94 : 58">94</span><span class="text-base">/100</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 h-1.5 rounded-full bg-brand-softmist" aria-hidden="true">
                        <div class="h-full rounded-full bg-linear-to-r from-brand-warmred to-brand-darkred transition-all duration-700 motion-reduce:transition-none" style="width: 94%" :style="{ width: (ai ? 94 : 58) + '%' }"></div>
                    </div>

                    <dl class="relative mt-5">
                        {{-- Garis kolom di tengah jarak label & isi: 7rem + setengah gap (0.5rem), dikurangi setengah lebar span (0.375rem) --}}
                        <x-sketch.rule vertical :delay="600" class="hidden sm:block text-brand-darkred/30 -top-1 -bottom-1 w-3 sm:left-[7.125rem]" />

                        @foreach ($cvDocRows as $row)
                            <div class="relative grid gap-1.5 py-4 sm:grid-cols-[7rem_1fr] sm:gap-4">
                                <x-sketch.rule :delay="$loop->index * 100" class="text-brand-darkred/30 -left-1 -right-1 -top-1.5 h-3" />
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-ink/50 sm:pt-1">{{ $row['label'] }}</dt>
                                <dd>
                                    <div x-show="!ai" x-cloak>
                                        <p class="text-sm leading-relaxed text-brand-ink/55">{{ $row['before'] }}</p>
                                        <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-brand-signal/10 px-2 py-0.5 text-[11px] font-semibold text-brand-signal">
                                            <x-landing.icon name="x" class="w-3 h-3" /> {{ $row['issue'] }}
                                        </span>
                                    </div>
                                    <div x-show="ai">
                                        <p class="text-sm leading-relaxed text-brand-ink">{!! $highlightKeywords($row['after']) !!}</p>
                                        <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                            <x-landing.icon name="check" class="w-3 h-3" /> {{ $row['fix'] }}
                                        </span>
                                    </div>
                                </dd>
                            </div>
                        @endforeach
                        <div class="relative grid gap-1.5 py-4 sm:grid-cols-[7rem_1fr] sm:gap-4">
                            <x-sketch.rule :delay="count($cvDocRows) * 100" class="text-brand-darkred/30 -left-1 -right-1 -top-1.5 h-3" />
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-ink/50 sm:pt-1">Keahlian</dt>
                            <dd>
                                <ul x-show="!ai" x-cloak class="flex flex-wrap gap-1.5">
                                    @foreach ($cvSkills['before'] as $skill)
                                        <li class="rounded-sm bg-brand-softmist px-2 py-1 text-xs text-brand-ink/55">{{ $skill }}</li>
                                    @endforeach
                                </ul>
                                <ul x-show="ai" class="flex flex-wrap gap-1.5">
                                    @foreach ($cvSkills['after'] as $skill)
                                        <li class="rounded-sm bg-brand-darkred/10 px-2 py-1 text-xs font-semibold text-brand-darkred">{{ $skill }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="relative flex flex-wrap items-center justify-between gap-3 px-5 py-4 md:px-7 text-xs font-semibold">
                    <x-sketch.rule :delay="700" class="text-brand-darkred/30 -left-1 -right-1 -top-1.5 h-3" />
                    <p x-show="ai" class="flex items-center gap-2 text-brand-ink">
                        <x-landing.icon name="link" class="w-4 h-4 text-brand-darkred" />
                        Siap dipakai melamar · Tersinkron dengan 12 lowongan mitra
                    </p>
                    <p x-show="!ai" x-cloak class="flex items-center gap-2 text-brand-signal">
                        <x-landing.icon name="eye" class="w-4 h-4" />
                        {{ count($cvDocRows) + 1 }} masalah ditemukan pada CV asli
                    </p>
                    <p class="text-brand-ink/50" x-text="ai ? 'Matikan Mode AI untuk melihat CV aslinya' : 'Nyalakan Mode AI untuk memperbaikinya'">Matikan Mode AI untuk melihat CV aslinya</p>
                </div>
            </div>

            {{-- Tiga kemampuan utama, masing-masing dengan pratinjau kecil --}}
            <div class="lg:col-span-5 grid gap-6 text-left">
                {{-- Format standar ATS: bagian CV yang berhasil dibaca parser --}}
                <article class="rounded-card bg-white p-5 md:p-6 shadow-softpill ring-1 ring-brand-ink/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-display text-sm font-bold uppercase tracking-wide text-brand-darkred">01</p>
                            <h3 class="mt-1 text-lg font-semibold">Format Standar ATS</h3>
                        </div>
                        <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-brand-signal to-brand-deepred text-white">
                            <x-landing.icon name="fileText" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-brand-ink/70">Terbaca otomatis oleh sistem HRD mitra IDUKA tanpa error parsing.</p>
                    <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-xs font-medium text-brand-ink/80">
                        @foreach ($cvParsedSections as $section)
                            <li class="flex items-center gap-2">
                                <span class="flex w-4 h-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><x-landing.icon name="check" class="w-2.5 h-2.5" /></span>
                                {{ $section }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 border-t border-dashed border-brand-ink/15 pt-3 text-xs font-semibold text-emerald-700">{{ count($cvParsedSections) }}/{{ count($cvParsedSections) }} bagian terbaca sempurna</p>
                </article>

                {{-- Kata kunci industri per jurusan, bisa dipilih --}}
                <article x-data="{ jurusan: 'RPL' }" class="rounded-card bg-white p-5 md:p-6 shadow-softpill ring-1 ring-brand-ink/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-display text-sm font-bold uppercase tracking-wide text-brand-darkred">02</p>
                            <h3 class="mt-1 text-lg font-semibold">Rekomendasi Kata Kunci Industri</h3>
                        </div>
                        <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-brand-signal to-brand-deepred text-white">
                            <x-landing.icon name="search" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-brand-ink/70">AI menyelaraskan keahlian teknismu dengan kualifikasi lowongan. Pilih jurusan:</p>
                    <div class="mt-4 flex flex-wrap gap-1.5" role="group" aria-label="Pilih jurusan">
                        @foreach ($jurusanFilters as $code => $label)
                            <button type="button" @click="jurusan = '{{ $code }}'" :aria-pressed="(jurusan === '{{ $code }}').toString()" aria-pressed="{{ $code === 'RPL' ? 'true' : 'false' }}"
                                class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $code === 'RPL' ? 'bg-brand-darkred text-white' : 'bg-brand-softmist text-brand-ink/70 hover:text-brand-darkred' }}"
                                :class="{ 'bg-brand-darkred text-white': jurusan === '{{ $code }}', 'bg-brand-softmist text-brand-ink/70 hover:text-brand-darkred': jurusan !== '{{ $code }}' }">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-4 grid" aria-live="polite">
                        @foreach ($cvKeywords as $code => $keywords)
                            <ul x-show="jurusan === '{{ $code }}'" @if ($code !== 'RPL') x-cloak @endif class="col-start-1 row-start-1 flex flex-wrap content-start gap-1.5">
                                @foreach ($keywords as $keyword)
                                    <li class="rounded-md border border-dashed border-brand-darkred/30 px-2 py-1 text-xs font-semibold text-brand-darkred">{{ $keyword }}</li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </article>

                {{-- Portofolio otomatis: sumber data sekolah -> poin pencapaian --}}
                <article class="relative overflow-hidden rounded-card bg-linear-135 from-brand-darkred to-brand-deepred p-5 md:p-6 text-white shadow-softpill">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-display text-sm font-bold uppercase tracking-wide text-[#F5C2C7]">03</p>
                            <h3 class="mt-1 text-lg font-semibold">Auto-Generate Portofolio</h3>
                        </div>
                        <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-white/15">
                            <x-landing.icon name="award" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-white/75">Tugas praktik, jurnal PKL, dan sertifikat LSP dirangkai menjadi poin pencapaian profesional.</p>
                    <div class="mt-5 flex items-center gap-3">
                        <ul class="flex flex-1 flex-wrap gap-1.5">
                            @foreach ($cvSources as $source)
                                <li class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold">
                                    <x-landing.icon :name="$source['icon']" class="w-3.5 h-3.5" /> {{ $source['label'] }}
                                </li>
                            @endforeach
                        </ul>
                        <x-sketch.arrow class="w-8 h-4 shrink-0 text-[#F5C2C7]" />
                        <span class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-brand-darkred">Poin Pencapaian</span>
                    </div>
                </article>
            </div>

            {{-- Ajakan --}}
            <div class="lg:col-span-12 flex flex-col gap-5 rounded-card bg-brand-ink p-6 md:p-8 text-white sm:flex-row sm:items-center sm:justify-between">
                <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/80">
                    @foreach (['Gratis untuk siswa & alumni', 'Ekspor PDF ramah ATS', 'Langsung dipakai melamar'] as $perk)
                        <li class="flex items-center gap-2"><x-landing.icon name="check" class="w-4 h-4 text-brand-warmred" /> {{ $perk }}</li>
                    @endforeach
                </ul>
                <div class="flex shrink-0 flex-wrap items-center gap-4">
                    <a href="{{ route('bkk.berita.detail', 'panduan-praktis-siswa-5-langkah-membuat-cv-digital-standar-ats') }}" class="text-sm font-semibold text-white/80 underline-offset-4 hover:text-white hover:underline">
                        Baca panduan CV ATS
                    </a>
                    <a href="{{ $cvStudioUrl }}" class="group inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-black/20 transition-transform hover:-translate-y-0.5">
                        Buka AI CV Studio
                        <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     4. LOWONGAN PKL & KERJA TERVERIFIKASI
     ============================================================ --}}
<section id="lowongan" class="relative bg-white px-6 py-20 md:py-28 scroll-mt-24" x-data="jobFilter(@js($jobFilterData))">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Lowongan PKL &amp; Kerja Terverifikasi</p>
                <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight text-left">
                    Eksplorasi Lowongan <x-sketch.underline>Industri Mitra</x-sketch.underline>
                </h2>
                <p class="mt-6 text-base md:text-lg leading-relaxed text-brand-ink/70">
                    Setiap lowongan sudah diverifikasi tim BKK, mulai dari legalitas perusahaan hingga kesesuaian kompensasi.
                </p>
            </div>
            <a href="{{ route('bkk.lowongan') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand-darkred">
                Lihat semua lowongan
                <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
            </a>
        </div>

        {{-- Filter langsung menyaring kartu di bawah; tombol submit mencari di seluruh lowongan (halaman /bkk/lowongan) --}}
        <form action="{{ route('bkk.lowongan') }}" method="GET" role="search" class="mt-10 rounded-card bg-brand-softmist p-4 md:p-5">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto] lg:items-center">
                <label class="relative block">
                    <span class="sr-only">Kata kunci lowongan</span>
                    <x-landing.icon name="search" class="pointer-events-none absolute left-4 top-1/2 w-4 h-4 -translate-y-1/2 text-brand-ink/40" />
                    <input
                        type="search"
                        name="q"
                        x-model.debounce.150ms="q"
                        placeholder="Cari posisi, perusahaan, atau lokasi…"
                        class="w-full rounded-full border border-brand-ink/10 bg-white py-3 pl-11 pr-4 text-sm outline-none transition focus:border-brand-darkred focus:ring-4 focus:ring-brand-darkred/10"
                    >
                </label>

                <fieldset class="flex rounded-full bg-white p-1 ring-1 ring-brand-ink/10">
                    <legend class="sr-only">Kategori lowongan</legend>
                    @foreach (['' => 'Semua', 'pkl' => 'PKL Siswa', 'kerja' => 'Kerja Alumni'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="tipe" value="{{ $value }}" x-model="tipe" class="peer sr-only" @checked($value === '')>
                            <span class="block whitespace-nowrap rounded-full px-3.5 py-2 text-xs sm:text-sm font-semibold text-brand-ink/60 transition-colors peer-checked:bg-brand-darkred peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-darkred">{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <label class="block">
                    <span class="sr-only">Jurusan</span>
                    <select name="jurusan" x-model="jurusan" class="w-full rounded-full border border-brand-ink/10 bg-white py-3 pl-4 pr-10 text-sm font-semibold outline-none transition focus:border-brand-darkred focus:ring-4 focus:ring-brand-darkred/10">
                        <option value="all">Semua Jurusan</option>
                        @foreach ($jurusanFilters as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-darkred">
                    Cari di Semua Lowongan
                </button>
            </div>
        </form>

        @if (count($jobs) > 0)
            <p class="mt-5 text-sm text-brand-ink/60" aria-live="polite">
                Menampilkan <span class="font-semibold text-brand-ink" x-text="visibleCount">{{ count($jobs) }}</span> dari {{ count($jobs) }} lowongan terbaru
            </p>

            <div class="mt-5 grid gap-6 md:grid-cols-2">
                @foreach ($jobs as $i => $job)
                    @php [$deadlineLabel, $urgent] = $deadlineInfo($job['deadline']); @endphp
                    <article x-show="isVisible({{ $i }})" class="group relative flex h-full flex-col rounded-card bg-white p-5 md:p-6 text-left shadow-softpill ring-1 ring-brand-ink/5 transition-all duration-300 hover:-translate-y-1 hover:ring-brand-darkred/25">
                        <div class="flex items-start justify-between gap-4">
                            <x-landing.company-logo :mitra="$job['mitra']" class="w-12 h-12 rounded-xl text-base" />
                            <div class="flex flex-wrap justify-end gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                    <x-landing.icon name="shield" class="w-3 h-3" /> Terverifikasi
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $job['pkl'] ? 'bg-brand-darkred/10 text-brand-darkred' : 'bg-brand-ink text-white' }}">
                                    {{ $job['pkl'] ? 'PKL Siswa' : 'Kerja Alumni' }}
                                </span>
                            </div>
                        </div>

                        <h3 class="mt-5 text-lg font-semibold leading-snug line-clamp-2 transition-colors group-hover:text-brand-darkred">
                            <a href="{{ $job['url'] }}" class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-darkred rounded">{{ $job['title'] }}</a>
                        </h3>
                        <p class="mt-1 text-sm text-brand-ink/60 line-clamp-1">{{ $job['company'] }}</p>

                        <ul class="mt-5 grid gap-2.5 text-sm text-brand-ink/75 sm:grid-cols-2">
                            <li class="flex items-start gap-2.5">
                                <x-landing.icon name="mapPin" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
                                <span class="line-clamp-1"><span class="sr-only">Lokasi: </span>{{ $job['lokasi'] }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-landing.icon name="school" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
                                <span class="line-clamp-1"><span class="sr-only">Jurusan: </span>{{ $job['jurusan'] }}</span>
                            </li>
                            @if ($job['gaji'])
                                <li class="flex items-start gap-2.5 sm:col-span-2">
                                    <x-landing.icon name="wallet" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
                                    <span class="line-clamp-1 font-medium text-brand-ink"><span class="sr-only">Gaji: </span>{{ $job['gaji'] }}</span>
                                </li>
                            @endif
                        </ul>

                        <div class="mt-auto pt-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-dashed border-brand-ink/15 pt-4">
                                <span class="flex items-center gap-1.5 text-xs font-semibold {{ $urgent ? 'text-brand-signal' : 'text-brand-ink/50' }}">
                                    <x-landing.icon name="clock" class="w-3.5 h-3.5" />
                                    {{ $deadlineLabel }}
                                </span>
                                <a href="{{ $job['url'] }}#btn-lamar" class="inline-flex items-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-md shadow-brand-darkred/20 transition-transform hover:-translate-y-0.5">
                                    <x-landing.icon name="bulb" class="w-4 h-4" />
                                    Lamar dengan CV AI
                                    <span class="sr-only">: {{ $job['title'] }}</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div x-show="visibleCount === 0" x-cloak class="mt-6 rounded-card border border-dashed border-brand-ink/20 p-10 text-center">
                <x-landing.icon name="search" class="mx-auto w-10 h-10 text-brand-ink/30" />
                <p class="mt-4 text-base font-semibold">Belum ada lowongan terbaru yang cocok</p>
                <p class="mt-1 text-sm text-brand-ink/60">Coba kata kunci lain, atau cari di seluruh lowongan aktif.</p>
                <button type="button" @click="reset()" class="mt-5 text-sm font-semibold text-brand-darkred underline-offset-4 hover:underline">Hapus filter</button>
            </div>
        @else
            <div class="mt-8 flex flex-col items-center gap-3 rounded-card border-2 border-dashed border-brand-ink/15 bg-white px-8 py-12 text-center">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                    <x-landing.icon name="briefcase" class="w-7 h-7" />
                </span>
                <h3 class="mt-2 font-display text-xl font-bold uppercase tracking-wide">Belum Ada Lowongan Aktif</h3>
                <p class="max-w-md text-sm leading-relaxed text-brand-ink/65">
                    Saat ini belum ada lowongan PKL atau kerja yang dipublikasikan oleh mitra industri. Lowongan baru akan segera diumumkan.
                </p>
                <a href="{{ route('bkk.lowongan') }}" class="group mt-3 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-darkred/20 transition-transform hover:-translate-y-0.5">
                    Kunjungi Portal Lowongan
                    <x-sketch.arrow class="w-5 h-2.5 transition-transform group-hover:translate-x-1" />
                </a>
            </div>
        @endif
    </div>
</section>

{{-- ============================================================
     6. ALUR 4 LANGKAH MENUJU KARIER
     ============================================================ --}}
<section id="alur" class="relative bg-white px-6 py-20 md:py-28 scroll-mt-24">
    <div class="max-w-6xl mx-auto">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Alur Layanan</p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                <x-sketch.frame>4 Langkah Menuju Karier</x-sketch.frame>
            </h2>
        </div>

        <ol class="mt-14 grid gap-y-4 lg:grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr] lg:gap-x-3 items-stretch">
            @foreach ($steps as $s => $step)
                <li class="relative flex flex-col rounded-card bg-brand-softmist p-6 text-left">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-4xl font-bold uppercase tracking-wide text-brand-darkred">{{ $step['no'] }}</span>
                        <span class="flex w-10 h-10 items-center justify-center rounded-full bg-white text-brand-darkred shadow-sm">
                            <x-landing.icon :name="$step['icon']" class="w-5 h-5" />
                        </span>
                    </div>
                    <h3 class="mt-5 text-base font-semibold leading-snug">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-brand-ink/70">{{ $step['desc'] }}</p>
                </li>
                @unless ($loop->last)
                    <li aria-hidden="true" class="flex items-center justify-center text-brand-darkred">
                        <x-sketch.arrow :delay="$s * 250" class="hidden lg:block w-8 h-4" />
                        <x-sketch.arrow direction="down" :delay="$s * 250" class="lg:hidden w-3 h-6" />
                    </li>
                @endunless
            @endforeach
        </ol>
    </div>
</section>

{{-- ============================================================
     7. BERITA & INFORMASI TERKINI
     ============================================================ --}}
<section id="berita" class="relative bg-brand-softmist px-6 py-20 md:py-28 scroll-mt-24">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">BKK News &amp; Agenda</p>
                <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight text-left">
                    Berita &amp; Informasi <x-sketch.underline>Terkini</x-sketch.underline>
                </h2>
            </div>
            <a href="{{ route('bkk.berita') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand-darkred">
                Semua berita &amp; agenda
                <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
            </a>
        </div>

        @if (count($news) > 0)
            <div class="mt-10 grid gap-6 md:grid-cols-2">
                @foreach ($news as $n => $item)
                    @if ($n === 0)
                        <x-landing.berita-card :item="$item" featured class="md:col-span-2" />
                    @else
                        <x-landing.berita-card :item="$item" />
                    @endif
                @endforeach
            </div>
        @else
            <div class="mt-10 flex flex-col items-center gap-3 rounded-card border-2 border-dashed border-brand-ink/15 bg-white px-8 py-12 text-center">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                    <x-landing.icon name="newspaper" class="w-7 h-7" />
                </span>
                <h3 class="mt-2 font-display text-xl font-bold uppercase tracking-wide">Belum Ada Berita Terbaru</h3>
                <p class="max-w-md text-sm leading-relaxed text-brand-ink/65">
                    Warta dan agenda bursa kerja khusus akan dipublikasikan secara berkala oleh tim BKK.
                </p>
                <a href="{{ route('bkk.berita') }}" class="group mt-3 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-darkred/20 transition-transform hover:-translate-y-0.5">
                    Kunjungi Portal Berita
                    <x-sketch.arrow class="w-5 h-2.5 transition-transform group-hover:translate-x-1" />
                </a>
            </div>
        @endif
    </div>
</section>

{{-- ============================================================
     8. FAQ & DUKUNGAN WHATSAPP
     ============================================================ --}}
<section id="faq" class="relative bg-white px-6 py-20 md:py-28 scroll-mt-24">
    <div class="max-w-6xl mx-auto grid gap-10 lg:gap-16 lg:grid-cols-[2fr_3fr] items-start">
        <div class="lg:sticky lg:top-32">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                <x-sketch.sparks>FAQ</x-sketch.sparks>
            </p>
            <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight text-left">
                Pertanyaan yang Sering Diajukan
            </h2>
            <p class="mt-4 text-base md:text-lg leading-relaxed text-brand-ink/70">
                Jawaban singkat seputar lowongan, PKL, AI CV Enhancer, dan kemitraan IDUKA.
            </p>

            <div class="mt-8 rounded-card bg-brand-softmist p-6">
                <h3 class="text-base font-semibold">Masih punya pertanyaan?</h3>
                <p class="mt-1 text-sm leading-relaxed text-brand-ink/70">Hubungi sekretariat BKK lewat WhatsApp, kami siap membantu.</p>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noreferrer" class="mt-5 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-brand-darkred to-brand-deepred px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    <x-landing.icon name="whatsapp" class="w-5 h-5" />
                    Chat via WhatsApp
                </a>
            </div>
        </div>

        <x-landing.faq :items="$faqs" />
    </div>
</section>
@endsection
