{{--
    Daftar lowongan PKL & kerja. Gaya mengikuti landing page sekolah (coretan tangan, merah marun).
    Data dari PublicController@lowongan: $lowongans (paginasi), $totalLowongan, $tipeCounts, dan filter aktif
    $searchQuery, $tipe, $jurusan, $lokasi, $urut. Semua filter berupa query string, jadi bisa dibagikan.
--}}
@php
    $navSection = 'lowongan';
    $whatsappUrl ??= 'https://wa.me/6281210868958';

    $tipeTabs = [
        ['value' => null, 'label' => 'Semua Lowongan', 'count' => $totalLowongan],
        ['value' => 'pkl', 'label' => 'Khusus PKL Siswa', 'count' => (int) ($tipeCounts['PKL'] ?? 0)],
        ['value' => 'kerja', 'label' => 'Full-Time Alumni', 'count' => (int) ($tipeCounts['Kerja'] ?? 0)],
    ];
    $activeTipe = in_array(strtolower((string) $tipe), ['pkl', 'kerja']) ? strtolower($tipe) : null;

    // Nilai jurusan dicocokkan sebagian ke kolom target_jurusan (teks bebas, mis. "Teknik Komputer & Jaringan (TKJ)")
    $jurusanOptions = [
        'all' => 'Semua Jurusan',
        'RPL' => 'RPL (Perangkat Lunak)',
        'TKJ' => 'TKJ (Jaringan & Komputer)',
        'Multimedia' => 'MM (Multimedia)',
        'Perbankan' => 'PKM (Perbankan & Keuangan)',
        'Otomasi' => 'TOI (Otomasi Industri)',
    ];
    $lokasiOptions = [
        'all' => 'Semua Lokasi',
        'bogor' => 'Bogor / Cibinong',
        'jakarta' => 'DKI Jakarta',
        'depok' => 'Depok / Bekasi',
        'karawang' => 'Kawasan Industri Karawang',
        'hybrid' => 'Remote / Hybrid',
    ];
    $urutOptions = [
        'newest' => 'Terbaru Ditambahkan',
        'deadline' => 'Segera Ditutup (Urgent)',
        'quota' => 'Sisa Kuota Terbanyak',
    ];

    $lastUpdated = $lowongans->getCollection()->max('updated_at');
    $hasFilter = $searchQuery || $activeTipe || ($jurusan && $jurusan !== 'all') || ($lokasi && $lokasi !== 'all');
    $selectClass = 'w-full appearance-none rounded-full border-2 border-brand-ink/10 bg-white py-3 pl-5 pr-11 text-sm font-medium text-brand-ink transition focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10 cursor-pointer';
@endphp

@extends('index.layouts.landing')

@section('title', 'Lowongan PKL & Kerja - BKK SMK Plus Pelita Nusantara')

@section('content')
{{-- ============================================================
     1. KEPALA HALAMAN & KONSOL PENCARIAN
     ============================================================ --}}
<section class="relative overflow-hidden bg-white px-6 pt-32 pb-16 md:pt-40 md:pb-20">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-size-[44px_44px] mask-[radial-gradient(ellipse_60%_70%_at_20%_20%,#000_50%,transparent_100%)]"></div>
        <div class="absolute right-6 top-28 hidden md:block w-40 h-28 bg-[radial-gradient(circle,var(--color-brand-mist)_2px,transparent_2.5px)] bg-size-[22px_22px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto">
        <div class="animate-fade-up">
            <nav aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-brand-ink/50">
                    <li><a href="{{ route('bkk.index') }}" class="transition-colors hover:text-brand-darkred">BKK Penus</a></li>
                    <li class="flex items-center gap-2"><x-landing.icon name="chevronRight" class="w-3.5 h-3.5" />Bursa Karir Siswa &amp; Alumni</li>
                    <li class="flex items-center gap-2"><x-landing.icon name="chevronRight" class="w-3.5 h-3.5" /><span aria-current="page" class="font-semibold text-brand-darkred">Lowongan Tersedia</span></li>
                </ol>
            </nav>

            <div class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-left font-display text-4xl sm:text-5xl lg:text-6xl font-bold uppercase tracking-wide leading-[1.05]">
                        Eksplorasi Peluang PKL &amp; Karier
                        <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500" class="text-brand-darkred">Industri</x-sketch.underline>
                    </h1>
                    <p class="mt-10 text-base md:text-lg leading-relaxed text-brand-ink/70">
                        Akses langsung rekrutmen terverifikasi dari mitra IDUKA resmi SMK Plus Pelita Nusantara. Prioritas seleksi untuk siswa aktif dan alumni berprestasi.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-darkred/10 px-4 py-2 text-sm font-semibold text-brand-darkred">
                        <span class="relative flex w-2.5 h-2.5"><span class="absolute inset-0 animate-ping rounded-full bg-brand-signal/60 motion-reduce:hidden"></span><span class="relative w-2.5 h-2.5 rounded-full bg-brand-signal"></span></span>
                        {{ $totalLowongan }} Lowongan Terverifikasi
                    </span>
                    <a href="{{ route('bkk.me.cv') }}" class="group inline-flex items-center gap-2 rounded-full border-2 border-brand-darkred/20 px-4 py-1.5 text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                        <x-landing.icon name="fileText" class="w-4 h-4" />
                        Standar CV Penus
                    </a>
                </div>
            </div>
        </div>

        {{-- Konsol pencarian: dropdown langsung mengirim form saat diganti --}}
        <form method="GET" action="{{ route('bkk.lowongan') }}" role="search" class="relative mt-12 md:mt-14 rounded-card bg-white p-5 md:p-6 shadow-softpill ring-1 ring-brand-ink/5">
            @if ($activeTipe)
                <input type="hidden" name="tipe" value="{{ $activeTipe }}">
            @endif
            <div class="flex flex-col gap-3">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <label for="cari-lowongan" class="sr-only">Cari lowongan</label>
                        <x-landing.icon name="search" class="pointer-events-none absolute left-5 top-1/2 w-5 h-5 -translate-y-1/2 text-brand-darkred" />
                        <input id="cari-lowongan" name="q" type="search" value="{{ $searchQuery }}" placeholder="Cari posisi, skill (Laravel, Mikrotik, SAP), atau nama mitra industri..."
                            class="w-full rounded-full border-2 border-brand-ink/10 bg-white py-3 pl-13 pr-5 text-sm text-brand-ink placeholder:text-brand-ink/40 transition focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred shrink-0 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                        Cari
                        <x-sketch.arrow class="w-6 h-3" />
                    </button>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([['jurusan', 'Jurusan', $jurusanOptions, $jurusan ?: 'all', 'school'], ['lokasi', 'Lokasi', $lokasiOptions, $lokasi ?: 'all', 'mapPin'], ['urut', 'Urutkan', $urutOptions, $urut ?: 'newest', 'chart']] as [$name, $label, $options, $selected, $icon])
                        <div class="relative">
                            <label for="filter-{{ $name }}" class="sr-only">{{ $label }}</label>
                            <select id="filter-{{ $name }}" name="{{ $name }}" onchange="this.form.submit()" class="{{ $selectClass }}">
                                @foreach ($options as $value => $text)
                                    <option value="{{ $value }}" @selected($selected === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                            <x-landing.icon :name="$icon" class="pointer-events-none absolute right-4 top-1/2 w-4 h-4 -translate-y-1/2 text-brand-darkred" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tipe lowongan: tipe aktif ditandai coretan bawah --}}
            <div class="relative mt-6 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pt-5">
                <x-sketch.rule class="text-brand-ink/15 left-0 right-0 -top-1.5 h-3" />
                <ul class="flex flex-wrap items-center gap-1">
                    @foreach ($tipeTabs as $tab)
                        @php $isActive = $activeTipe === $tab['value']; @endphp
                        <li>
                            <a href="{{ route('bkk.lowongan', array_filter(array_merge(request()->only('q', 'jurusan', 'lokasi', 'urut'), ['tipe' => $tab['value']]))) }}" @if ($isActive) aria-current="page" @endif
                                class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold transition-colors {{ $isActive ? 'text-brand-darkred' : 'text-brand-ink/65 hover:bg-brand-ink/5 hover:text-brand-ink' }}">
                                @if ($isActive)
                                    <x-sketch.underline size="sm">{{ $tab['label'] }}</x-sketch.underline>
                                @else
                                    <span>{{ $tab['label'] }}</span>
                                @endif
                                <span class="min-w-6 rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold {{ $isActive ? 'bg-brand-darkred text-white' : 'bg-brand-ink/5 text-brand-ink/50' }}">{{ $tab['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="flex items-center gap-2 text-xs font-semibold text-brand-ink/55">
                    <x-landing.icon name="shield" class="w-4 h-4 text-brand-darkred" />
                    100% IDUKA Terikat MoU Resmi
                </p>
            </div>
        </form>
    </div>
</section>

{{-- ============================================================
     2. PENGUMUMAN, DAFTAR LOWONGAN & PAGINASI
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-16 md:py-24">
    <div class="max-w-6xl mx-auto">
        {{-- Pengumuman PKL: warna & coretan sama dengan banner ajakan di footer --}}
        <div class="relative overflow-hidden rounded-card bg-linear-135 from-brand-darkred to-brand-deepred p-6 md:p-8 text-white shadow-softpill">
            <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 w-44 h-44 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <span class="flex w-12 h-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-[#F5C2C7]">
                        <x-landing.icon name="megaphone" class="w-6 h-6" />
                    </span>
                    <div>
                        <p class="text-left font-display text-lg md:text-xl font-bold uppercase tracking-wide leading-snug">
                            Pengumuman Penting PKL Gelombang II <span class="text-[#F5C2C7]">(Tahun Ajaran 2025/2026)</span>
                        </p>
                        <p class="mt-2 max-w-3xl text-sm md:text-base leading-relaxed text-white/85">
                            Siswa aktif Kelas XI diwajibkan menyelesaikan data portofolio, sertifikat kompetensi, dan CV digital di akun BKK sebelum tanggal <strong class="text-white">30 bulan ini</strong> untuk jadwal sinkronisasi perusahaan.
                        </p>
                    </div>
                </div>
                <a href="{{ route('bkk.me.cv.edit') }}" class="group inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-white px-6 py-3 text-sm font-bold text-brand-darkred transition-colors hover:bg-brand-mist md:self-center">
                    Lengkapi Profil CV
                    <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
                </a>
            </div>
        </div>

        <div class="mt-16 md:mt-20 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight">
                    @if ($hasFilter)
                        Hasil <x-sketch.underline>Pencarian</x-sketch.underline>
                    @else
                        Daftar Rekomendasi <x-sketch.underline>Terkini</x-sketch.underline>
                    @endif
                </h2>
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-brand-ink/50">
                    Menampilkan {{ $lowongans->count() }} dari {{ $lowongans->total() }} lowongan
                    @if ($hasFilter)
                        &middot; <a href="{{ route('bkk.lowongan') }}" class="text-brand-darkred hover:underline">Hapus filter</a>
                    @endif
                </p>
            </div>
            @if ($lastUpdated)
                <p class="flex shrink-0 items-center gap-2 text-xs font-medium text-brand-ink/55">
                    <span class="w-2 h-2 rounded-full bg-brand-signal"></span>
                    Diperbarui {{ $lastUpdated->locale('id')->diffForHumans() }} oleh Tim BKK
                </p>
            @endif
        </div>

        @if ($lowongans->isNotEmpty())
            <ul class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($lowongans as $lowongan)
                    <li><x-landing.lowongan-card :lowongan="$lowongan" /></li>
                @endforeach
            </ul>
        @else
            <div class="mt-10 flex flex-col items-center gap-3 rounded-card border-2 border-dashed border-brand-ink/15 bg-white px-8 py-14 text-center">
                <span class="flex w-16 h-16 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                    <x-landing.icon name="briefcase" class="w-8 h-8" />
                </span>
                <h3 class="mt-2 font-display text-xl font-bold uppercase tracking-wide">Belum Ada Lowongan yang Cocok</h3>
                <p class="max-w-md text-sm leading-relaxed text-brand-ink/65">
                    Coba kata kunci lain atau longgarkan filter jurusan dan lokasi. Lowongan baru dari mitra IDUKA ditambahkan secara berkala.
                </p>
                <a href="{{ route('bkk.lowongan') }}" class="group mt-3 inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    Lihat Semua Lowongan
                    <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                </a>
            </div>
        @endif

        <x-landing.pagination :paginator="$lowongans" label="lowongan" />
    </div>
</section>

{{-- ============================================================
     3. KONSULTASI KARIER
     ============================================================ --}}
<section class="relative bg-white px-6 py-20 md:py-24">
    <div class="relative max-w-6xl mx-auto grid gap-10 p-8 md:p-10 lg:grid-cols-[2fr_1fr] lg:items-center">
        <x-sketch.box />
        <div class="flex flex-col gap-6 md:flex-row md:items-start">
            <span class="flex w-20 h-20 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand-signal to-brand-deepred text-white">
                <x-landing.icon name="users" class="w-10 h-10" />
            </span>
            <div>
                <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold uppercase tracking-[0.2em]">
                    <span class="text-brand-darkred">Layanan Terpadu Siswa</span>
                    <span class="text-brand-ink/40">Bimbingan Konseling &amp; Hubin</span>
                </p>
                <h2 class="mt-3 text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight">
                    Butuh Konsultasi Penyaluran Karier &amp; Rekomendasi Magang?
                </h2>
                <p class="mt-4 leading-relaxed text-brand-ink/70">
                    Masih bingung memilih formasi PKL yang cocok dengan kompetensi jurusanmu? Guru BK dan Koordinator BKK Penus siap membantu bedah CV, simulasi interview, hingga penerbitan surat pengantar resmi.
                </p>
            </div>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-linear-to-r from-brand-darkred to-brand-deepred px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                <x-landing.icon name="whatsapp" class="w-5 h-5" />
                Konsultasi via WhatsApp BKK
            </a>
            <a href="#" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border-2 border-brand-darkred/20 px-6 py-3 text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                <x-landing.icon name="calendar" class="w-4 h-4" />
                Jadwalkan Konseling Tatap Muka
            </a>
        </div>
    </div>
</section>
@endsection
