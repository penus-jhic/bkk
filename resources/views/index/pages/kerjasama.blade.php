{{--
    Informasi & formulir kerja sama mitra IDUKA. Gaya mengikuti landing page sekolah (coretan tangan, merah marun).
    Data dari PublicController@kerjasama: $mitraList (mitra terverifikasi terbaru).
    Formulir dikirim ke PublicController@storeKerjasama (POST bkk.kerjasama.store).
--}}
@php
    $navSection = '';

    $metrics = [
        ['value' => '120+', 'label' => 'Mitra DUDI Aktif'],
        ['value' => '94%', 'label' => 'Kesiapan Kerja Siswa'],
        ['value' => '<24 Jam', 'label' => 'Respon Cepat Hubin'],
    ];

    $flow = [
        ['icon' => 'fileText', 'title' => 'Pengisian Kebutuhan', 'desc' => 'Perusahaan mengisi formulir kebutuhan talenta, posisi PKL, rekrutmen kerja, atau rencana program kelas industri.', 'note' => 'Estimasi: 5 Menit'],
        ['icon' => 'users', 'title' => 'Verifikasi & Diskusi', 'desc' => 'Tim Hubin & Pengurus BKK menghubungi HRD mitra untuk penyelarasan kualifikasi teknis dan jadwal rekrutmen.', 'note' => 'Maks. 1x24 Jam Kerja'],
        ['icon' => 'pen', 'title' => 'Penandatanganan MoU', 'desc' => 'Penerbitan surat perjanjian kerja sama resmi (MoU / PKS) bermaterai secara digital maupun luring di sekolah.', 'note' => 'Legalitas Terjamin'],
        ['icon' => 'flag', 'title' => 'Seleksi & Penempatan', 'desc' => 'Fasilitasi tes psikotes, wawancara kandidat di kampus BKK Penus, dan onboarding peserta magang/karyawan baru.', 'note' => 'Siap Bertugas'],
    ];

    $programs = [
        ['icon' => 'school', 'title' => 'Praktik Kerja Lapangan (PKL)', 'tag' => 'Durasi 3 - 6 Bulan Penuh', 'desc' => 'Penempatan siswa tingkat XI dan XII untuk menjalani magang intensif dengan modul kerja operasional nyata di perusahaan Anda, dilengkapi sertifikat kompetensi.', 'points' => ['Siswa terlatih etika kerja', 'Didampingi guru pamong']],
        ['icon' => 'briefcase', 'title' => 'Rekrutmen & Walk-In Interview', 'tag' => 'Fasilitas Kampus Gratis', 'desc' => 'Penyelenggaraan rekrutmen eksklusif lulusan baru (fresh graduates) langsung di aula sekolah dengan dukungan logistik tim BKK untuk tes tulis dan wawancara massal.', 'points' => ['Database 400+ lulusan/thn', 'Ruang wawancara AC & Lab Komputer']],
        ['icon' => 'factory', 'title' => 'Kelas Industri & TEFA', 'tag' => 'Sinkronisasi Kurikulum', 'desc' => 'Penyelarasan silabus mata pelajaran dengan Standard Operating Procedure (SOP) mitra sehingga siswa langsung paham teknologi spesifik perusahaan Anda.', 'points' => ['Teaching Factory bersama', 'Skema prioritas rekrutmen']],
        ['icon' => 'users', 'title' => 'Guru Tamu & Kunjungan Industri', 'tag' => 'Transfer Pengetahuan Praktis', 'desc' => 'Hadirkan praktisi atau pimpinan perusahaan Anda sebagai narasumber seminar vokasi, serta menerima sesi company visit edukatif siswa dan dewan guru.', 'points' => ['Branding perusahaan di civitas', 'CSR Pendidikan terukur']],
    ];

    $sectors = [
        'Teknologi Informasi & Software' => 'Teknologi Informasi & Software',
        'Manufaktur & Otomotif' => 'Manufaktur & Perakitan',
        'Perbankan & Jasa Keuangan' => 'Perbankan & Jasa Keuangan',
        'Logistik & Supply Chain' => 'Logistik, Ekspedisi & Gudang',
        'Retail & Distribusi' => 'Retail, E-Commerce & Perdagangan',
        'Media, Desain & Percetakan' => 'Media Digital & Creative Agency',
        'Lainnya' => 'Lainnya',
    ];
    $headcounts = [
        '1-5 Siswa' => '1 - 5 Orang',
        '6-15 Siswa' => '6 - 15 Orang',
        '16-30 Siswa' => '16 - 30 Orang',
        'Lebih dari 30 Siswa' => '> 30 Orang (Perekrutan Massal)',
    ];
    // Nilai disimpan apa adanya ke kolom jenis_kerjasama (JSON)
    $programChoices = ['PKL / Magang Siswa', 'Rekrutmen Lulusan / Walk-in', 'Kelas Industri / MoU Silabus', 'Guru Tamu & Kunjungan Industri'];
    $chosenPrograms = old('jenis_kerjasama', $errors->any() ? [] : ['PKL / Magang Siswa']);

    $contactRows = [
        ['icon' => 'mapPin', 'label' => 'Alamat Kampus', 'value' => 'Kampus SMK Plus Pelita Nusantara, Jl. Golf No. 1, Ciriung, Cibinong, Kab. Bogor, Jawa Barat 16918', 'href' => null, 'note' => null],
        ['icon' => 'mail', 'label' => 'Email Korespondensi Kemitraan', 'value' => 'kemitraan@smkpenus.sch.id', 'href' => 'mailto:kemitraan@smkpenus.sch.id', 'note' => 'Alternatif: bkk@smkpenus.sch.id'],
        ['icon' => 'whatsapp', 'label' => 'WhatsApp Resmi Hubin Penus', 'value' => '+62 812-8875-4321', 'href' => null, 'note' => 'Tersedia panggilan telepon kantor & chat instan'],
        ['icon' => 'clock', 'label' => 'Jam Operasional Layanan', 'value' => 'Senin – Jumat : 08.00 – 16.00 WIB', 'href' => null, 'note' => 'Sabtu – Minggu & Libur Nasional : Tutup'],
    ];

    $partnerFaqs = [
        ['q' => 'Apakah ada biaya administrasi untuk membuka walk-in interview atau rekrutmen di kampus?', 'a' => 'Tidak ada. Program rekrutmen lulusan dan seleksi walk-in interview di SMK Plus Pelita Nusantara disediakan secara gratis tanpa dipungut biaya fasilitas sebagai bentuk komitmen penyaluran kerja alumni BKK kami.'],
        ['q' => 'Kompetensi keahlian apa saja yang tersedia di SMK Plus Pelita Nusantara?', 'a' => 'Siswa kami terlatih dalam 3 klaster keahlian utama: 1) Rekayasa Perangkat Lunak & Jaringan Komputer, 2) Manajemen Perkantoran & Otomatisasi Administrasi, dan 3) Akuntansi & Keuangan Lembaga, lengkap dengan sertifikasi BNSP.'],
        ['q' => 'Bagaimana proses penandatanganan dokumen MoU kerja sama?', 'a' => 'Tim Hubin kami menyediakan draf nota kesepahaman (MoU) standar Kemendikbudristek yang dapat ditandatangani secara digital (e-sign dengan sertifikat elektronik) maupun pertemuan seremoni resmi secara tatap muka di kantor mitra atau di sekolah.'],
    ];

    $mapsQuery = 'SMK+Plus+Pelita+Nusantara+Cibinong+Bogor';
    $inputClass = 'w-full rounded-xl border-2 bg-white px-4 py-3 text-sm text-brand-ink placeholder:text-brand-ink/40 transition focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10';
    $fieldClass = fn (string $field) => $inputClass . ' ' . ($errors->has($field) ? 'border-brand-signal' : 'border-brand-ink/10');

    // Marquee mitra: satu putaran diulang sampai minimal 8 kartu supaya lebarnya selalu melebihi layar,
    // lalu dirender dua kali dan digeser -50% agar sambungannya tidak terlihat
    $marqueeMitra = $mitraList->isEmpty()
        ? collect()
        : collect(array_fill(0, (int) ceil(8 / $mitraList->count()), $mitraList))->flatten(1);
    $marqueeDuration = max(30, $marqueeMitra->count() * 5);
@endphp

@extends('index.layouts.landing')

@section('title', 'Kerja Sama Mitra - BKK SMK Plus Pelita Nusantara')

@push('styles')
<style>
    /* Tepi kiri & kanan memudar supaya kartu terlihat masuk dan keluar dengan halus */
    .mitra-marquee { mask-image: linear-gradient(to right, transparent, #000 8%, #000 92%, transparent); }
    .mitra-marquee-track { animation: mitra-marquee var(--marquee-duration, 40s) linear infinite; }
    .mitra-marquee:hover .mitra-marquee-track,
    .mitra-marquee:focus-within .mitra-marquee-track { animation-play-state: paused; }
    @keyframes mitra-marquee { to { transform: translateX(-50%); } }

    /* Tanpa animasi: cukup satu set kartu yang bisa digeser manual */
    @media (prefers-reduced-motion: reduce) {
        .mitra-marquee { overflow-x: auto; scrollbar-width: none; }
        .mitra-marquee-track { animation: none; }
        .mitra-marquee-track > [aria-hidden="true"], .mitra-marquee .marquee-repeat { display: none; }
    }
</style>
@endpush

@section('content')
{{-- ============================================================
     1. HERO KEMITRAAN
     ============================================================ --}}
<section class="relative overflow-hidden bg-white px-6 pt-32 pb-20 md:pt-40 md:pb-28">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-size-[44px_44px] mask-[radial-gradient(ellipse_70%_60%_at_30%_30%,#000_60%,transparent_100%)]"></div>
        <div class="absolute -top-40 -right-32 w-140 h-140 rounded-full bg-brand-signal/10 blur-[120px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto grid gap-16 lg:grid-cols-[1.15fr_1fr] lg:gap-12 lg:items-center">
        <div class="animate-fade-up">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                <x-sketch.sparks>Portal Hubungan Industri (Hubin &amp; BKK)</x-sketch.sparks>
            </p>
            <h1 class="mt-6 text-left font-display text-4xl sm:text-5xl lg:text-6xl font-bold uppercase tracking-wide leading-[1.05]">
                Bangun Kemitraan Strategis Bersama
                <span class="block mt-2 text-brand-darkred">SMK Plus Pelita <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500">Nusantara</x-sketch.underline></span>
            </h1>
            <p class="mt-10 max-w-2xl text-base md:text-lg leading-relaxed text-brand-ink/70">
                Akses talenta muda siap kerja dengan kompetensi teruji di bidang Teknologi Informasi, Administrasi Perkantoran, dan Akuntansi Bisnis. Kami membuka peluang program PKL terstruktur, rekrutmen lulusan langsung, hingga sinkronisasi kurikulum industri.
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-4">
                <a href="#form-kemitraan" class="group inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    Ajukan Formulir Kerja Sama
                    <x-sketch.arrow :delay="600" class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                </a>
                <button type="button" onclick="alert('Mengunduh Company Profile & Silabus Vokasi SMK Plus Pelita Nusantara (PDF)...')" class="inline-flex items-center gap-2 rounded-full border-2 border-brand-darkred/20 bg-white px-6 py-3 text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                    <x-landing.icon name="download" class="w-4 h-4" />
                    Unduh Company Profile &amp; Silabus (PDF)
                </button>
            </div>

            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-px overflow-hidden rounded-card bg-brand-ink/10 ring-1 ring-brand-ink/10">
                @foreach ($metrics as $metric)
                    <div class="flex flex-col bg-white px-4 py-4">
                        <dt class="text-[11px] sm:text-xs text-brand-ink/60">{{ $metric['label'] }}</dt>
                        <dd class="order-first font-display text-2xl md:text-3xl font-bold uppercase tracking-wide text-brand-darkred">{{ $metric['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="relative">
            <div class="relative overflow-hidden rounded-card shadow-softpill ring-1 ring-brand-ink/5">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBILTNE1MvwstCaVbES78F_3AryC-yYnYOq0zXQfjiNBFJcHrYuY9FSX-kt52bbmiSl21incTc5wzBjNmEMvMAc1piyhzfYHZMHmVRBQra3VikvJ_6dPVcZOXHXhWiL3JnlIcTXS4lEk7ltzgY1qknuAMFuG7eeDpqHVVmfE6q90TzBiaIhuSp7Qkuj73NsOUx191pnOLc3aWl_IysSl2jO2uDLg1NOEf3f_tY7A8BCW4bwRwEYPKq_" alt="Sesi mentoring perwakilan industri bersama siswa magang SMK" class="w-full aspect-4/3 object-cover bg-brand-softmist">
                {{-- Keterangan di atas foto, karena bagian bawahnya tertutup lencana "Mitra Tersertifikasi" --}}
                <div class="absolute inset-0 bg-linear-to-b from-brand-ink/85 via-brand-ink/10 to-transparent"></div>
                <div class="absolute inset-x-5 top-5 text-white">
                    <p class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-white px-2.5 py-0.5 font-bold text-brand-darkred">MoU DUDI Resmi</span>
                        <span class="text-white/75">Link &amp; Match Vokasi</span>
                    </p>
                    <p class="mt-2 text-sm font-semibold leading-snug">Penguatan Ekosistem Vokasi Berbasis Standardisasi Industri Nasional</p>
                </div>
            </div>
            <x-sketch.corner class="-left-4 -top-4 w-24 h-10 md:w-32 md:h-12" />
            <x-sketch.corner :delay="350" class="-right-4 -bottom-4 rotate-180 w-24 h-10 md:w-32 md:h-12" />

            {{-- Lencana kepercayaan menumpuk di kiri bawah foto --}}
            <div class="absolute -bottom-10 left-6 hidden sm:flex items-center gap-3 rounded-card bg-white p-4 pr-6 shadow-softpill ring-1 ring-brand-ink/5">
                <span class="flex w-11 h-11 shrink-0 items-center justify-center rounded-full bg-brand-darkred text-white">
                    <x-landing.icon name="badgeCheck" class="w-6 h-6" />
                </span>
                <span class="flex flex-col">
                    <span class="font-semibold leading-tight">Mitra Tersertifikasi</span>
                    <span class="text-xs text-brand-ink/60">Sesuai Regulasi Ditjen Pendidikan Vokasi</span>
                </span>
            </div>
        </div>
    </div>

    @if ($mitraList->isNotEmpty())
        {{-- Mitra yang sudah terverifikasi --}}
        <div class="relative max-w-6xl mx-auto mt-20 md:mt-24">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.25em] text-brand-ink/50">
                Telah Bergabung <x-sketch.underline size="sm">Bersama Kami</x-sketch.underline>
            </p>

            {{-- py: ruang untuk bayangan & efek angkat kartu, karena mask memotong apa pun di luar kotaknya --}}
            <div class="mitra-marquee mt-8 -mx-6 py-4 sm:mx-0" style="--marquee-duration: {{ $marqueeDuration }}s">
                <div class="mitra-marquee-track flex w-max">
                    @foreach ([false, true] as $duplicate)
                        {{-- pr-4 = gap-4, supaya jarak di sambungan kedua set sama dengan jarak antar kartu --}}
                        <ul class="flex gap-4 pr-4" @if ($duplicate) aria-hidden="true" @else aria-label="Mitra IDUKA terverifikasi" @endif>
                            @foreach ($marqueeMitra as $i => $mitra)
                                <li class="{{ $i >= $mitraList->count() ? 'marquee-repeat' : '' }} group flex w-80 shrink-0 items-center gap-4 rounded-card bg-white p-4 shadow-softpill ring-1 ring-brand-ink/5 transition-all duration-300 hover:-translate-y-1 hover:ring-brand-darkred/25">
                                    <x-landing.company-logo :mitra="$mitra" class="w-14 h-14 rounded-xl text-base" />
                                    <div class="min-w-0">
                                        <p class="flex items-start gap-1.5 font-semibold leading-snug">
                                            <span class="line-clamp-2 transition-colors group-hover:text-brand-darkred">{{ $mitra->nama_perusahaan }}</span>
                                            <x-landing.icon name="badgeCheck" class="mt-0.5 w-4 h-4 shrink-0 text-brand-darkred" />
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-brand-ink/60">{{ $mitra->sektor_industri }}</p>
                                        @if ($mitra->kota)
                                            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-ink/45">
                                                <x-landing.icon name="mapPin" class="w-3 h-3 text-brand-darkred" />
                                                {{ $mitra->kota }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>

{{-- ============================================================
     2. ALUR KEMITRAAN
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Proses Terstruktur &amp; Cepat</p>
                <h2 class="mt-4 text-left font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                    Alur Mudah Kemitraan <x-sketch.underline>Industri</x-sketch.underline>
                </h2>
            </div>
            <p class="max-w-md leading-relaxed text-brand-ink/70">
                Prosedur birokrasi yang ramping dan transparan agar perusahaan Anda dapat segera menjalin kolaborasi produktif tanpa hambatan administratif.
            </p>
        </div>

        <ol class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($flow as $i => $step)
                <li class="relative flex flex-col rounded-card bg-white p-6 shadow-sm ring-1 ring-brand-ink/5">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-5xl font-bold leading-none {{ $i === 0 ? 'text-brand-darkred' : 'text-brand-ink/15' }}">{{ sprintf('%02d', $i + 1) }}</span>
                        <span class="flex w-11 h-11 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                            <x-landing.icon :name="$step['icon']" class="w-5 h-5" />
                        </span>
                    </div>
                    <h3 class="mt-6 text-left font-display text-lg font-bold uppercase tracking-wide">{{ $step['title'] }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-brand-ink/70">{{ $step['desc'] }}</p>
                    <p class="relative mt-5 pt-4 text-xs font-semibold uppercase tracking-[0.15em] text-brand-darkred">
                        <x-sketch.rule :delay="$i * 120" class="text-brand-darkred/30 left-0 right-0 -top-1.5 h-3" />
                        {{ $step['note'] }}
                    </p>
                    {{-- Panah coretan ke langkah berikutnya (desktop) --}}
                    @if ($i < count($flow) - 1)
                        <span class="absolute -right-5 top-10 z-10 hidden text-brand-darkred lg:block"><x-sketch.arrow :delay="$i * 200" class="w-8 h-4" /></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ============================================================
     3. BENTUK PROGRAM KERJA SAMA
     ============================================================ --}}
<section class="relative bg-white px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Skema Kolaborasi Komprehensif</p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                <x-sketch.frame>Bentuk Program Kerja Sama Industri (DUDI)</x-sketch.frame>
            </h2>
            <p class="mt-6 text-base md:text-lg leading-relaxed text-brand-ink/70">Kami menawarkan integrasi berkelanjutan yang menguntungkan industri mitra melalui penyediaan sumber daya manusia berdaya saing tinggi.</p>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($programs as $program)
                <div class="group flex flex-col rounded-card bg-brand-softmist p-6 transition-all duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-softpill hover:ring-1 hover:ring-brand-darkred/15">
                    <span class="flex w-12 h-12 items-center justify-center rounded-2xl bg-white text-brand-darkred shadow-sm transition-colors group-hover:bg-brand-darkred group-hover:text-white">
                        <x-landing.icon :name="$program['icon']" class="w-6 h-6" />
                    </span>
                    <h3 class="mt-5 text-left font-display text-lg font-bold uppercase tracking-wide leading-snug">{{ $program['title'] }}</h3>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.15em] text-brand-darkred">{{ $program['tag'] }}</p>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-brand-ink/70">{{ $program['desc'] }}</p>
                    <ul class="mt-5 space-y-2 border-t border-dashed border-brand-ink/15 pt-4 text-sm">
                        @foreach ($program['points'] as $point)
                            <li class="flex items-center gap-2"><x-landing.icon name="check" class="w-4 h-4 shrink-0 text-brand-darkred" />{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     4. FORMULIR & KONTAK SEKRETARIAT
     ============================================================ --}}
<section id="form-kemitraan" class="relative bg-brand-softmist px-6 py-20 md:py-28 scroll-mt-24">
    <div class="max-w-6xl mx-auto grid gap-12 lg:grid-cols-[1.4fr_1fr] lg:items-start">
        <div class="relative bg-white p-7 sm:p-10" @if ($errors->any()) x-data x-init="$el.scrollIntoView({ block: 'start' })" @endif>
            <x-sketch.box />
            <div class="flex items-start gap-4">
                <span class="flex w-12 h-12 shrink-0 items-center justify-center rounded-full bg-brand-darkred text-white">
                    <x-landing.icon name="handshake" class="w-6 h-6" />
                </span>
                <div>
                    <h2 class="text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight">Formulir Pengajuan Kemitraan DUDI</h2>
                    <p class="mt-1 text-sm text-brand-ink/60">Mohon lengkapi data resmi kantor Anda di bawah ini.</p>
                </div>
            </div>

            @if (session('success'))
                <div role="status" class="relative mt-8 rounded-xl bg-brand-darkred/5 py-4 pr-4 pl-9">
                    <x-sketch.rule bold vertical class="text-brand-darkred top-1 bottom-1 left-2 w-3" />
                    <p class="flex items-center gap-2 font-semibold text-brand-darkred">
                        <x-landing.icon name="check" class="w-5 h-5" />
                        Pengajuan Berhasil Terkirim!
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-brand-ink/70">{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-8 rounded-xl border-2 border-brand-signal/30 bg-brand-signal/5 p-4 text-sm">
                    <p class="font-semibold text-brand-darkred">Pengajuan belum terkirim. Periksa kembali isian berikut:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-brand-ink/75">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('bkk.kerjasama.store') }}" class="mt-8 flex flex-col gap-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="nama_perusahaan" class="text-sm font-semibold">Nama Perusahaan / Instansi *</label>
                        <input id="nama_perusahaan" name="nama_perusahaan" type="text" required maxlength="200" value="{{ old('nama_perusahaan') }}" placeholder="PT. Solusi Digital Nusantara" class="mt-1.5 {{ $fieldClass('nama_perusahaan') }}">
                    </div>
                    <div>
                        <label for="bidang_usaha" class="text-sm font-semibold">Bidang Industri *</label>
                        <select id="bidang_usaha" name="bidang_usaha" required class="mt-1.5 {{ $fieldClass('bidang_usaha') }}">
                            <option value="">Pilih Bidang Usaha...</option>
                            @foreach ($sectors as $value => $label)
                                <option value="{{ $value }}" @selected(old('bidang_usaha') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="nama_pic" class="text-sm font-semibold">Nama Penanggung Jawab / HRD *</label>
                        <input id="nama_pic" name="nama_pic" type="text" required maxlength="150" value="{{ old('nama_pic') }}" placeholder="Bpk. Hendra Wijaya, S.Psi" class="mt-1.5 {{ $fieldClass('nama_pic') }}">
                    </div>
                    <div>
                        <label for="jabatan_pic" class="text-sm font-semibold">Jabatan Penanggung Jawab *</label>
                        <input id="jabatan_pic" name="jabatan_pic" type="text" required maxlength="150" value="{{ old('jabatan_pic') }}" placeholder="HR Recruitment Manager" class="mt-1.5 {{ $fieldClass('jabatan_pic') }}">
                    </div>
                    <div>
                        <label for="no_telepon" class="text-sm font-semibold">Nomor WhatsApp / Seluler *</label>
                        <input id="no_telepon" name="no_telepon" type="tel" required maxlength="50" value="{{ old('no_telepon') }}" placeholder="0812XXXXXXXX" class="mt-1.5 {{ $fieldClass('no_telepon') }}">
                    </div>
                    <div>
                        <label for="email_resmi" class="text-sm font-semibold">Email Resmi Kantor *</label>
                        <input id="email_resmi" name="email_resmi" type="email" required maxlength="150" value="{{ old('email_resmi') }}" placeholder="recruitment@perusahaan.co.id" class="mt-1.5 {{ $fieldClass('email_resmi') }}">
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="alamat_perusahaan" class="text-sm font-semibold">Alamat Kantor *</label>
                        <textarea id="alamat_perusahaan" name="alamat_perusahaan" rows="3" required placeholder="Jl. Raya Industri No. 10, Kota / Kabupaten" class="mt-1.5 {{ $fieldClass('alamat_perusahaan') }}">{{ old('alamat_perusahaan') }}</textarea>
                    </div>
                    <div>
                        <label for="estimasi_kebutuhan" class="text-sm font-semibold">Estimasi Kebutuhan Talenta *</label>
                        <select id="estimasi_kebutuhan" name="estimasi_kebutuhan" required class="mt-1.5 {{ $fieldClass('estimasi_kebutuhan') }}">
                            <option value="">Pilih Jumlah Kandidat...</option>
                            @foreach ($headcounts as $value => $label)
                                <option value="{{ $value }}" @selected(old('estimasi_kebutuhan') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold">Jenis Program yang Diminati (Bisa pilih lebih dari satu) *</legend>
                    <div class="mt-2 grid gap-2.5 sm:grid-cols-2">
                        @foreach ($programChoices as $choice)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-brand-ink/10 p-3 text-sm transition-colors hover:border-brand-darkred/30 has-checked:border-brand-darkred has-checked:bg-brand-darkred/5">
                                <input type="checkbox" name="jenis_kerjasama[]" value="{{ $choice }}" @checked(in_array($choice, (array) $chosenPrograms)) class="w-4 h-4 shrink-0 accent-brand-darkred">
                                {{ $choice }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="pesan_tambahan" class="text-sm font-semibold">Catatan Kualifikasi / Deskripsi Kebutuhan</label>
                    <textarea id="pesan_tambahan" name="pesan_tambahan" rows="3" placeholder="Contoh: Kami memerlukan 3 siswa jurusan RPL/TKJ untuk support instalasi jaringan dan 2 siswa Akuntansi untuk staf administrasi logistik..." class="mt-1.5 {{ $fieldClass('pesan_tambahan') }}">{{ old('pesan_tambahan') }}</textarea>
                </div>

                <p class="flex items-start gap-2 text-sm text-brand-ink/65">
                    <x-landing.icon name="clock" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
                    <span>Tim Hubin BKK Penus menjamin konfirmasi respons maksimal <strong class="text-brand-ink">1 x 24 jam kerja</strong> via WhatsApp/Email.</span>
                </p>
                <button type="submit" class="group inline-flex w-full items-center justify-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred py-4 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    <x-landing.icon name="send" class="w-4 h-4" />
                    Kirim Pengajuan Kemitraan
                </button>
            </form>
        </div>

        <aside class="flex flex-col gap-8">
            <div class="rounded-card bg-white p-7 shadow-sm ring-1 ring-brand-ink/5">
                <h3 class="flex items-center gap-2 font-display text-xl font-bold uppercase tracking-wide">
                    <x-landing.icon name="phoneCall" class="w-5 h-5 text-brand-darkred" />
                    Sekretariat BKK &amp; Hubin
                </h3>
                <ul class="mt-6">
                    @foreach ($contactRows as $i => $row)
                        <li class="relative flex items-start gap-3.5 py-4 first:pt-0 last:pb-0">
                            @if ($i > 0)
                                <x-sketch.rule :delay="$i * 100" class="text-brand-darkred/25 -left-1 -right-1 -top-1.5 h-3" />
                            @endif
                            <x-landing.icon :name="$row['icon']" class="w-5 h-5 mt-0.5 shrink-0 text-brand-darkred" />
                            <span class="flex min-w-0 flex-col">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-ink/50">{{ $row['label'] }}</span>
                                @if ($row['href'])
                                    <a href="{{ $row['href'] }}" class="mt-0.5 font-semibold text-brand-darkred hover:underline">{{ $row['value'] }}</a>
                                @else
                                    <span class="mt-0.5 text-sm font-medium leading-relaxed">{{ $row['value'] }}</span>
                                @endif
                                @if ($row['note'])
                                    <span class="text-xs text-brand-ink/55">{{ $row['note'] }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative rounded-card bg-white p-6 shadow-sm ring-1 ring-brand-ink/5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-display text-lg font-bold uppercase tracking-wide">Peta Lokasi Kampus</h3>
                    <span class="rounded-full bg-brand-softmist px-2.5 py-0.5 text-xs font-semibold text-brand-ink/60">Cibinong, Bogor</span>
                </div>
                <div class="relative mt-4 h-56 overflow-hidden rounded-xl ring-1 ring-brand-ink/10">
                    <iframe title="Peta Lokasi Kampus BKK Pelita Nusantara" src="https://maps.google.com/maps?q={{ $mapsQuery }}&t=&z=15&ie=UTF8&iwloc=&output=embed" class="h-full w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-brand-ink/60">
                    <span>Akses 7 menit dari Tol Jagorawi (Gerbang Sirkuit Sentul / Cibinong)</span>
                    <a href="https://maps.google.com/?q={{ $mapsQuery }}" target="_blank" rel="noreferrer" class="group inline-flex items-center gap-1.5 font-semibold text-brand-darkred">
                        Buka di Maps
                        <x-sketch.arrow class="w-5 h-2.5 transition-transform group-hover:translate-x-1" />
                    </a>
                </div>
            </div>
        </aside>
    </div>
</section>

{{-- ============================================================
     5. FAQ KEMITRAAN
     ============================================================ --}}
<section class="relative bg-white px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto grid gap-10 lg:gap-16 lg:grid-cols-[2fr_3fr] items-start">
        <div class="lg:sticky lg:top-32">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                <x-sketch.sparks>Frequently Asked Questions</x-sketch.sparks>
            </p>
            <h2 class="mt-3 text-left font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                Pertanyaan Seputar Kemitraan DUDI
            </h2>
        </div>
        <x-landing.faq :items="$partnerFaqs" />
    </div>
</section>
@endsection
