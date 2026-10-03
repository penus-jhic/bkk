{{--
    Detail lowongan PKL & kerja. Gaya mengikuti landing page sekolah (coretan tangan, merah marun).
    Data dari PublicController@lowonganDetail: $lowongan (null kalau slug tidak ditemukan, dengan lamarans_count),
    $relatedLowongan, $totalLowongan.
--}}
@php
    $navSection = 'lowongan';

    if ($lowongan) {
        $mitra = $lowongan->mitra;
        $isPkl = $lowongan->tipe === 'PKL';
        $companyName = $mitra->nama_perusahaan ?? 'Mitra Industri BKK';
        $shareUrl = url()->current();

        $deadline = $lowongan->deadline;
        $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false) : null;
        $isOpen = $lowongan->status === 'Aktif' && ($daysLeft === null || $daysLeft >= 0);
        $deadlineText = $deadline ? $deadline->locale('id')->translatedFormat('j F Y') : 'Tanpa batas waktu';
        $deadlineHint = match (true) {
            $daysLeft === null => null,
            $daysLeft < 0 => 'Pendaftaran ditutup',
            $daysLeft === 0 => 'Hari terakhir',
            default => $daysLeft . ' hari lagi',
        };

        $kuota = max(1, (int) $lowongan->kuota);
        $pelamar = (int) $lowongan->lamarans_count;
        $kuotaUnit = $isPkl ? 'Siswa' : 'Formasi';
        $fill = min(100, (int) round($pelamar / $kuota * 100));

        $mouRange = $mitra?->tanggal_mou_mulai && $mitra?->tanggal_mou_selesai
            ? $mitra->tanggal_mou_mulai->format('Y') . '-' . $mitra->tanggal_mou_selesai->format('Y')
            : null;

        $requirements = array_values(array_filter((array) $lowongan->persyaratan_json));
        $benefits = array_values(array_filter((array) $lowongan->benefit_json));

        // Coretan hanya di kata terakhir judul, supaya garisnya tidak ikut melebar saat judul terlipat
        $titleWords = explode(' ', trim($lowongan->judul));
        $titleLastWord = array_pop($titleWords);
        $titleFirstWords = implode(' ', $titleWords);

        $highlights = [
            ['label' => 'Model Penugasan', 'value' => $lowongan->lokasi, 'icon' => 'mapPin'],
            ['label' => 'Kategori Posisi', 'value' => $lowongan->kategori_posisi ?: $lowongan->tipe_badge, 'icon' => 'briefcase'],
            ['label' => 'Kompensasi', 'value' => $lowongan->gaji_kompensasi ?: 'Sesuai ketentuan mitra', 'icon' => 'wallet'],
        ];

        $selectionSteps = [
            ['title' => 'Seleksi Berkas CV BKK', 'desc' => 'Kurasi profil siswa, portofolio, dan validasi data absensi sekolah.'],
            ['title' => 'Tes Praktik Sekolah', 'desc' => 'Live coding / konfigurasi lab di Lab Komputer SMK Penus.'],
            ['title' => 'Wawancara DUDI', 'desc' => 'Sesi tatap muka online / offline dengan tim HRD & Tech Lead mitra.'],
            ['title' => $isPkl ? 'Surat Tugas PKL' : 'Penempatan Kerja', 'desc' => $isPkl ? 'Penerbitan Surat Tugas resmi sekolah dan pembekalan pra-keberangkatan.' : 'Penandatanganan kontrak kerja dan onboarding bersama tim HRD mitra.'],
        ];
    }
@endphp

@extends('index.layouts.landing')

@section('title', ($lowongan->judul ?? 'Detail Lowongan') . ' - BKK SMK Plus Pelita Nusantara')

@section('content')
@if (!$lowongan)
    {{-- Slug tidak ditemukan --}}
    <section class="relative bg-white px-6 pt-32 pb-20 md:pt-40 md:pb-28">
        <div class="max-w-2xl mx-auto text-center animate-fade-up">
            <span class="mx-auto flex w-20 h-20 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                <x-landing.icon name="briefcase" class="w-10 h-10" />
            </span>
            <h1 class="mt-8 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide">
                Lowongan <x-sketch.underline tone="text-brand-signal">Tidak Ditemukan</x-sketch.underline>
            </h1>
            <p class="mt-8 text-base leading-relaxed text-brand-ink/70">
                Lowongan yang Anda cari sudah ditutup, dihapus, atau tautannya salah. Lihat lowongan aktif lainnya dari mitra IDUKA BKK Penus.
            </p>
            <a href="{{ route('bkk.lowongan') }}" class="group mt-8 inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                Lihat Semua Lowongan
                <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
            </a>
        </div>
    </section>
@else
{{-- ============================================================
     1. KEPALA: breadcrumb, perusahaan, posisi, ringkasan & bagikan
     ============================================================ --}}
<section class="relative bg-white px-6 pt-32 pb-14 md:pt-40 md:pb-16">
    <div aria-hidden="true" class="pointer-events-none absolute right-6 top-28 hidden md:block w-40 h-28 bg-[radial-gradient(circle,var(--color-brand-mist)_2px,transparent_2.5px)] bg-size-[22px_22px]"></div>

    <div class="relative max-w-6xl mx-auto animate-fade-up">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <nav aria-label="Breadcrumb" class="min-w-0">
                <ol class="flex items-center gap-2 text-sm text-brand-ink/50">
                    <li class="shrink-0"><a href="{{ route('bkk.index') }}" class="flex items-center gap-1.5 transition-colors hover:text-brand-darkred"><x-landing.icon name="home" class="w-4 h-4" />Beranda</a></li>
                    <li class="flex shrink-0 items-center gap-2"><x-landing.icon name="chevronRight" class="w-3.5 h-3.5" /><a href="{{ route('bkk.lowongan') }}" class="transition-colors hover:text-brand-darkred">Lowongan PKL &amp; Kerja</a></li>
                    <li class="flex min-w-0 items-center gap-2"><x-landing.icon name="chevronRight" class="w-3.5 h-3.5 shrink-0" /><span aria-current="page" class="truncate font-medium text-brand-ink sm:max-w-sm">{{ $lowongan->judul }}</span></li>
                </ol>
            </nav>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-softmist px-3 py-1 text-xs font-semibold text-brand-ink/70">
                    <span class="w-2 h-2 rounded-full {{ $isOpen ? 'bg-brand-signal' : 'bg-brand-ink/30' }}"></span>
                    {{ $lowongan->tipe_badge ?: ($isPkl ? 'PKL Siswa' : 'Kerja Alumni') }}
                </span>
                <span class="rounded-full bg-brand-darkred/10 px-3 py-1 text-xs font-semibold text-brand-darkred">
                    ID: PENUS-{{ $isPkl ? 'PKL' : 'KRJ' }}-{{ str_pad($lowongan->id, 3, '0', STR_PAD_LEFT) }}
                </span>
            </div>
        </div>

        <div class="mt-10 md:mt-12 flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
            <div class="flex min-w-0 flex-col gap-6 sm:flex-row sm:items-start">
                <div class="relative shrink-0 self-start">
                    <x-landing.company-logo :mitra="$mitra" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl text-2xl shadow-softpill" />
                    @if ($mitra?->is_verified)
                        <span title="Mitra Terverifikasi" class="absolute -right-2 -bottom-2 flex w-8 h-8 items-center justify-center rounded-full bg-white text-brand-darkred shadow-sm">
                            <x-landing.icon name="badgeCheck" class="w-5 h-5" />
                        </span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-softmist px-3 py-1 text-xs font-semibold text-brand-ink/75">
                            <x-landing.icon name="building" class="w-3.5 h-3.5 text-brand-darkred" />
                            {{ $companyName }}
                        </span>
                        @if ($mitra?->is_verified)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-darkred px-3 py-1 text-xs font-semibold text-white">
                                <x-landing.icon name="shield" class="w-3.5 h-3.5" />
                                Terverifikasi BKK Penus
                            </span>
                        @endif
                        @if ($mouRange)
                            <span class="rounded-full border border-brand-darkred/20 px-3 py-1 text-xs font-semibold text-brand-darkred">MoU DUDI {{ $mouRange }}</span>
                        @endif
                    </div>
                    <h1 class="mt-4 max-w-3xl text-left font-display text-3xl md:text-4xl lg:text-5xl font-bold uppercase tracking-wide leading-tight">
                        @if ($titleFirstWords !== '')
                            {{ $titleFirstWords }}
                        @endif
                        <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500">{{ $titleLastWord }}</x-sketch.underline>
                    </h1>
                    <ul class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-brand-ink/70">
                        <li class="flex items-center gap-2 font-semibold text-brand-darkred">
                            <x-landing.icon name="school" class="w-4 h-4 shrink-0" />
                            {{ $isPkl ? 'PKL Siswa SMK' : 'Kerja Alumni' }} — {{ $lowongan->target_jurusan }}
                        </li>
                        <li class="flex items-center gap-2"><x-landing.icon name="mapPin" class="w-4 h-4 shrink-0 text-brand-darkred" />{{ $lowongan->lokasi }}</li>
                        @if ($lowongan->gaji_kompensasi)
                            <li class="flex items-center gap-2"><x-landing.icon name="wallet" class="w-4 h-4 shrink-0 text-brand-darkred" />{{ $lowongan->gaji_kompensasi }}</li>
                        @endif
                        <li class="flex items-center gap-2"><x-landing.icon name="calendar" class="w-4 h-4 shrink-0 text-brand-darkred" />Tutup {{ $deadlineText }}</li>
                    </ul>
                </div>
            </div>

            @php $roundButton = 'inline-flex w-11 h-11 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred transition-colors hover:bg-brand-darkred hover:text-white'; @endphp
            <div class="flex shrink-0 items-center gap-2.5">
                <button type="button" x-data="copyLink(@js($shareUrl))" @click="copy()" title="Bagikan Lowongan" aria-label="Salin tautan lowongan" class="{{ $roundButton }}">
                    <x-landing.icon name="link" class="w-5 h-5" x-show="!copied" />
                    <x-landing.icon name="check" class="w-5 h-5" x-show="copied" x-cloak />
                    <span role="status" class="sr-only" x-text="copied ? 'Tautan lowongan berhasil disalin' : ''"></span>
                </button>
                <button type="button" x-data="{ saved: false }" @click="saved = !saved" title="Simpan ke Bookmark Siswa" aria-label="Simpan ke Bookmark Siswa" :aria-pressed="saved.toString()"
                    class="{{ $roundButton }}" :class="{ 'bg-brand-darkred! text-white!': saved }">
                    <x-landing.icon name="bookmark" class="w-5 h-5" x-bind:class="{ 'fill-current': saved }" />
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     2. ISI LOWONGAN & KARTU LAMARAN
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-16 md:py-24" x-data="{ apply: false }" @keydown.escape.window="apply = false">
    <div class="max-w-6xl mx-auto grid gap-12 lg:grid-cols-[1fr_22rem] lg:items-start xl:gap-16">
        <div class="flex min-w-0 flex-col gap-8">
            {{-- Ringkasan peran & profil perusahaan --}}
            <article class="relative rounded-card bg-white p-7 md:p-9 shadow-sm ring-1 ring-brand-ink/5">
                <x-sketch.corner class="-left-3 -top-3 w-20 h-9" />
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-left font-display text-2xl font-bold uppercase tracking-wide">Ringkasan Peran &amp; Profil Perusahaan</h2>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-brand-ink/40">DUDI Partner Info</span>
                </div>
                <p class="mt-6 text-base md:text-lg leading-relaxed text-brand-ink/80">{{ $lowongan->deskripsi }}</p>
                @if ($mitra)
                    <p class="mt-4 leading-relaxed text-brand-ink/70">
                        <strong class="font-semibold text-brand-ink">{{ $companyName }}</strong>
                        bergerak di sektor {{ $mitra->sektor_industri }}{{ $mitra->kota ? ', berkantor di ' . $mitra->kota : '' }},
                        dengan status kemitraan <strong class="font-semibold text-brand-ink">{{ $mitra->status_kemitraan ?: 'Mitra IDUKA' }}</strong> di SMK Plus Pelita Nusantara.
                        @if ($mitra->website)
                            <a href="{{ Str::startsWith($mitra->website, 'http') ? $mitra->website : 'https://' . $mitra->website }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-darkred hover:underline">Kunjungi situs perusahaan</a>.
                        @endif
                    </p>
                @endif

                <dl class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach ($highlights as $item)
                        <div class="rounded-xl bg-brand-softmist/70 p-4">
                            <dt class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-ink/50">
                                <x-landing.icon :name="$item['icon']" class="w-3.5 h-3.5 text-brand-darkred" />
                                {{ $item['label'] }}
                            </dt>
                            <dd class="mt-1.5 text-sm font-semibold leading-snug">{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </article>

            {{-- Kualifikasi --}}
            @if ($requirements)
                <article class="rounded-card bg-white p-7 md:p-9 shadow-sm ring-1 ring-brand-ink/5">
                    <h2 class="text-left font-display text-2xl font-bold uppercase tracking-wide">
                        Kualifikasi &amp; <x-sketch.underline>Persyaratan</x-sketch.underline>
                    </h2>
                    <ul class="mt-8 space-y-4">
                        @foreach ($requirements as $i => $requirement)
                            <li class="flex gap-4">
                                <span class="mt-1.5 shrink-0 text-brand-darkred"><x-sketch.arrow :delay="$i * 150" class="w-8 h-4" /></span>
                                <span class="leading-relaxed text-brand-ink/80">{{ $requirement }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($isPkl)
                        <div class="relative mt-8 rounded-xl bg-brand-darkred/5 py-4 pr-4 pl-8">
                            <x-sketch.rule bold vertical class="text-brand-darkred top-1 bottom-1 left-2 w-3" />
                            <p class="text-sm font-semibold">Dokumen yang disiapkan saat dinyatakan lolos berkas:</p>
                            <p class="mt-1 text-sm leading-relaxed text-brand-ink/70">1. Surat Izin Orang Tua bertandatangan basah, 2. Transkrip Nilai Rapor Semester Terakhir, 3. Surat Pengantar Resmi BKK Penus.</p>
                        </div>
                    @endif
                </article>
            @endif

            {{-- Keuntungan --}}
            @if ($benefits)
                <article class="rounded-card bg-white p-7 md:p-9 shadow-sm ring-1 ring-brand-ink/5">
                    <h2 class="text-left font-display text-2xl font-bold uppercase tracking-wide">
                        Keuntungan &amp; Fasilitas {{ $isPkl ? 'Peserta PKL' : 'Karyawan' }}
                    </h2>
                    <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ($benefits as $benefit)
                            <li class="flex items-start gap-3 rounded-xl bg-brand-softmist/70 p-4">
                                <span class="flex w-9 h-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-darkred shadow-sm">
                                    <x-landing.icon name="award" class="w-4.5 h-4.5" />
                                </span>
                                <span class="text-sm font-medium leading-relaxed">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif

            {{-- Alur seleksi: nomor besar, dipisah panah coretan --}}
            <article class="rounded-card bg-white p-7 md:p-9 shadow-sm ring-1 ring-brand-ink/5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-left font-display text-2xl font-bold uppercase tracking-wide">Alur Tahapan Seleksi Masuk</h2>
                    <span class="text-xs font-semibold text-brand-darkred">Total Waktu: ~14 Hari Kerja</span>
                </div>
                <ol class="mt-8 grid gap-6 md:grid-cols-4 md:gap-4">
                    @foreach ($selectionSteps as $i => $step)
                        <li class="relative">
                            <div class="flex items-center gap-3">
                                <span class="font-display text-4xl font-bold leading-none {{ $i === 0 ? 'text-brand-darkred' : 'text-brand-ink/20' }}">{{ sprintf('%02d', $i + 1) }}</span>
                                @if ($i < count($selectionSteps) - 1)
                                    <span class="hidden flex-1 text-brand-darkred/40 md:block"><x-sketch.arrow :delay="$i * 200" class="w-full max-w-12 h-4" /></span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-semibold">{{ $step['title'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-brand-ink/60">{{ $step['desc'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </article>
        </div>

        {{-- Kartu lamaran: bingkai coretan, menempel saat discroll di desktop --}}
        <aside class="flex flex-col gap-8 lg:sticky lg:top-32">
            <div class="relative bg-white p-6 sm:p-7">
                <x-sketch.box />
                <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-ink/50">Status Pendaftaran {{ $isPkl ? 'PKL' : 'Kerja' }}</p>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <p class="font-display text-xl font-bold uppercase tracking-wide {{ $isOpen ? '' : 'text-brand-ink/50' }}">
                        {{ $isOpen ? ($isPkl ? 'Terbuka Untuk Siswa' : 'Terbuka Untuk Alumni') : 'Pendaftaran Ditutup' }}
                    </p>
                    <span class="shrink-0 rounded-full bg-brand-darkred/10 px-2.5 py-1 text-xs font-bold text-brand-darkred">{{ $kuota }} {{ $kuotaUnit }}</span>
                </div>

                <div class="mt-5 rounded-xl bg-brand-softmist/70 p-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-brand-ink/60">Pelamar Terdaftar</span>
                        <span class="font-bold">{{ $pelamar }} untuk {{ $kuota }} {{ $kuotaUnit }}</span>
                    </div>
                    <div class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-linear-to-r from-brand-signal to-brand-darkred" style="width: {{ max(4, $fill) }}%"></div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-xs">
                        <span class="text-brand-ink/60">Batas Akhir:</span>
                        <span class="font-semibold text-brand-darkred">{{ $deadlineText }}@if ($deadlineHint) <span class="font-normal text-brand-ink/50">({{ $deadlineHint }})</span>@endif</span>
                    </div>
                </div>

                <dl class="mt-4 divide-y divide-dashed divide-brand-ink/10 text-sm">
                    @foreach ([['clock', 'Tipe Program', $lowongan->tipe_badge ?: $lowongan->tipe], ['school', 'Target Jurusan', $lowongan->target_jurusan], ['check', 'Metode Seleksi', 'Online via BKK Penus']] as [$icon, $label, $value])
                        <div class="flex items-start justify-between gap-4 py-2.5">
                            <dt class="flex shrink-0 items-center gap-2 text-brand-ink/60"><x-landing.icon :name="$icon" class="w-4 h-4 text-brand-darkred" />{{ $label }}</dt>
                            <dd class="text-right font-semibold">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($isOpen)
                    <button type="button" @click="apply = true" class="group mt-5 inline-flex w-full items-center justify-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred py-4 text-base font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                        Lamar Posisi Ini Sekarang
                        <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                    </button>
                    <p class="mt-3 text-center text-xs text-brand-ink/60">Sistem BKK otomatis menyertakan <strong class="text-brand-ink">CV Digital &amp; Nilai Rapor</strong> akun Anda.</p>
                @else
                    <p class="mt-5 rounded-full bg-brand-ink/5 py-3.5 text-center text-sm font-semibold text-brand-ink/50">Pendaftaran untuk lowongan ini sudah ditutup</p>
                @endif

                <div class="mt-5 flex flex-col gap-2">
                    <a href="{{ route('bkk.me.cv') }}" class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-brand-darkred/20 px-4 py-2.5 text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                        <x-landing.icon name="eye" class="w-4 h-4" />
                        Preview CV Kamu Sebelum Melamar
                    </a>
                    @if ($isPkl)
                        <a href="#" class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-brand-ink/60 transition-colors hover:bg-brand-ink/5 hover:text-brand-ink">
                            <x-landing.icon name="download" class="w-4 h-4" />
                            Unduh Format Surat Izin Orang Tua (PDF)
                        </a>
                    @endif
                </div>

                <div class="mt-5 flex items-center gap-3 rounded-xl bg-brand-softmist/70 p-4">
                    <span class="flex w-11 h-11 shrink-0 items-center justify-center rounded-full bg-brand-darkred font-display text-sm font-bold text-white">SR</span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-brand-ink/50">Koordinator Hubin &amp; BKK</p>
                        <p class="truncate text-sm font-semibold">Ibu Sri Rahayu, M.Pd.</p>
                        <p class="text-xs text-brand-ink/60">Konsultasi via Ruang BKK Gd. A Lt. 2</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-card bg-linear-135 from-brand-darkred to-brand-deepred p-6 text-white shadow-softpill">
                <p class="flex items-center gap-2 font-display text-lg font-bold uppercase tracking-wide">
                    <x-landing.icon name="bulb" class="w-5 h-5 text-[#F5C2C7]" />
                    Butuh Panduan Portfolio?
                </p>
                <p class="mt-2 text-sm leading-relaxed text-white/85">
                    Ikuti klinik bimbingan CV &amp; simulasi wawancara setiap hari Rabu sepulang sekolah di Lab Multimedia BKK Penus.
                </p>
            </div>
        </aside>
    </div>

    {{-- Konfirmasi lamaran: dikirim ke Portal Siswa (perlu masuk sebagai siswa/alumni) --}}
    @if ($isOpen)
        <div x-show="apply" x-cloak x-transition.opacity class="fixed inset-0 z-60 flex items-center justify-center bg-brand-ink/70 p-4 backdrop-blur-sm" @click.self="apply = false">
            <form method="POST" action="{{ route('bkk.me.daftar', $lowongan->slug ?: $lowongan->id) }}" role="dialog" aria-modal="true" aria-labelledby="judul-lamaran"
                class="relative w-full max-w-lg rounded-card bg-white p-6 sm:p-8 shadow-2xl">
                @csrf
                <div class="flex items-center justify-between gap-4">
                    <h2 id="judul-lamaran" class="flex items-center gap-2.5 font-display text-xl font-bold uppercase tracking-wide">
                        <x-landing.icon name="send" class="w-5 h-5 text-brand-darkred" />
                        Konfirmasi Pengajuan {{ $isPkl ? 'PKL' : 'Lamaran' }}
                    </h2>
                    <button type="button" @click="apply = false" aria-label="Tutup" class="flex w-9 h-9 shrink-0 items-center justify-center rounded-full bg-brand-ink/5 transition-colors hover:bg-brand-ink/10">
                        <x-landing.icon name="x" class="w-4 h-4" />
                    </button>
                </div>

                <p class="mt-5 text-sm text-brand-ink/70">Anda akan mendaftar pada posisi:</p>
                <div class="relative mt-2 rounded-xl bg-brand-softmist/70 py-3 pr-3 pl-7">
                    <x-sketch.rule bold vertical class="text-brand-darkred top-1 bottom-1 left-1.5 w-3" />
                    <p class="font-semibold">{{ $lowongan->judul }}</p>
                    <p class="text-sm text-brand-darkred">{{ $companyName }}</p>
                </div>

                <label for="lamaran-portofolio" class="mt-5 block text-xs font-semibold uppercase tracking-[0.15em]">Tautan Portofolio / GitHub Siswa</label>
                <input id="lamaran-portofolio" name="portofolio_url" type="url" placeholder="https://github.com/username/project-anda" class="mt-2 w-full rounded-xl border-2 border-brand-ink/10 px-4 py-2.5 text-sm focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10">

                <label for="lamaran-catatan" class="mt-4 block text-xs font-semibold uppercase tracking-[0.15em]">Catatan Tambahan untuk Kaprogli &amp; DUDI (Opsional)</label>
                <textarea id="lamaran-catatan" name="catatan" rows="3" placeholder="Sebutkan proyek sekolah terbaik yang pernah Anda buat..." class="mt-2 w-full rounded-xl border-2 border-brand-ink/10 px-4 py-2.5 text-sm focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10"></textarea>

                <label class="mt-4 flex items-start gap-2.5 text-sm leading-snug text-brand-ink/70">
                    <input type="checkbox" required class="mt-0.5 w-4 h-4 shrink-0 accent-brand-darkred">
                    Saya menyatakan data di profil siswa BKK benar dan bersedia mengikuti seluruh tata tertib PKL SMK Plus Pelita Nusantara.
                </label>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="apply = false" class="rounded-full px-5 py-2.5 text-sm font-semibold text-brand-ink/70 transition-colors hover:bg-brand-ink/5">Batal</button>
                    <button type="submit" class="group inline-flex items-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25">
                        Kirim Lamaran Sekarang
                        <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
                    </button>
                </div>
            </form>
        </div>
    @endif
</section>

{{-- ============================================================
     3. LOWONGAN LAIN
     ============================================================ --}}
@if ($relatedLowongan->isNotEmpty())
    <section class="relative bg-white px-6 py-20 md:py-28">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Peluang Karir Alternatif</p>
                    <h2 class="mt-3 text-left font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                        <x-sketch.frame class="-ml-4 md:-ml-5">Rekomendasi Lowongan PKL &amp; Kerja Terkait</x-sketch.frame>
                    </h2>
                </div>
                <a href="{{ route('bkk.lowongan') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand-darkred">
                    Lihat Semua {{ $totalLowongan }} Lowongan Aktif
                    <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                </a>
            </div>

            <ul class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedLowongan as $i => $related)
                    {{-- Di tablet hanya 2 kolom, jadi kartu ketiga disembunyikan supaya tidak tersisa sendirian --}}
                    <li @class(['md:max-lg:hidden' => $i === 2])><x-landing.lowongan-card :lowongan="$related" /></li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
@endif
@endsection
